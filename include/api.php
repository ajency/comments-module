<?php
/*
 * Api configuration and methods of the plugin
 * 
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
if(is_plugin_active('json-rest-api/plugin.php')){
    
    class CommentsModuleAPI {

        /**
         * Server object
         *
         * @var WP_JSON_ResponseHandler
         */
        protected $server;

        /**
         * Constructor
         *
         * @param WP_JSON_ResponseHandler $server Server object
         */
        public function __construct(WP_JSON_ResponseHandler $server) {
                $this->server = $server;
        }

        /*Register Routes*/
        public function register_routes( $routes ) {

             $routes['/aj_comments/comments/add'] = array(
                array( array( $this, 'add_comment'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON),
                );
             
             $routes['/aj_comments/comments/(?P<comment_id>\d+)'] = array(
                array( array( $this, 'edit_comment' ),      WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
                array( array( $this, 'delete_comment' ),    WP_JSON_Server::DELETABLE ),
                );
             
             $routes['/aj_comments/comments/(?P<comment_obj_id>\d+)/type/(?P<comment_type>\w+)'] = array(
                array( array( $this, 'get_comments'), WP_JSON_Server::READABLE ),
                );
             
             $routes['/aj_comments/replies/(?P<comment_id>\d+)'] = array(
                array( array( $this, 'get_replies'), WP_JSON_Server::READABLE ),
                );

            
            return $routes;
        }
        
        /*
         * function to add a comment
         * @param array $data $_POST array
         * 
         * @returns response JSON response on successfully adding a comment
         */
        public function add_comment($data){
            
            global $aj_commentsmodule;
            
            // check if custom comment type registered
            if(! $aj_commentsmodule->is_registered_comment_type($data['comment_type'])){
                wp_send_json_error(array('msg' => 'comment type not registered'));
            }
            
            $comment_post_ID = (isset($data['comment_post_ID'])) ? (int) $data['comment_post_ID'] : 0;

            if($data['comment_type'] == 'post'){
                $post = get_post($comment_post_ID);
            }
            
            $comment_author       = ( isset($data['author']) )  ? trim(strip_tags($data['author'])) : null;
            $comment_author_email = ( isset($data['email']) )   ? trim($data['email']) : null;
            $comment_author_url   = ( isset($data['url']) )     ? trim($data['url']) : null;
            $comment_content      = ( isset($data['comment']) ) ? trim($data['comment']) : null;
            $comment_type         = ( isset($data['comment_type']) ) ? trim($data['comment_type']) : null;

            $comment_parent = $data['comment_parent'];
            
            $commenter_id = ( isset($data['user_id']) ) ? $data['user_id'] : null;

            $user = new WP_User($commenter_id);

            
            if ( $user->exists() ) {
                 	if ( empty( $user->display_name ) )
                            $user->display_name=$user->user_login;
                $comment_author       = wp_slash( $user->display_name );
                $comment_author_email = wp_slash( $user->user_email );
                $comment_author_url   = wp_slash( $user->user_url );
            }else{
                //todo handle checks on comments posting in case not logged in user
            }
            
            $user_ID = $commenter_id;

            $commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

            $comment_id = wp_new_comment( $commentdata );
            
            if (intval($comment_id) > 0){
                wp_send_json_success(array('comment_id'=>$comment_id));   
            }else{
                wp_send_json_error(array('msg' => 'error posting comment'));
            }

        }
        
        /*
         * function to update a comment/reply
         * @param int $comment_id
         * @param array $data $_POST 'comment' comment text to be udated 
         * 
         */
        public function edit_comment($comment_id,$data){
            
            $commentarr = array();
                    
            $comment_id = intval($comment_id);
            
            $comment_data = get_comment($comment_id);
            if(is_null($comment_data)){
                wp_send_json_error(array('msg' => 'Invalid Comment id'));
            }
            
            /*if ( ! current_user_can( 'edit_comment', (int) $comment_id ) ){
               wp_send_json_error(array('msg' => 'User Cannot edit commenet'));
            }
            */
            
            $commentarr['comment_ID'] = $comment_id;
            $commentarr['comment_content'] = $data['comment'];

            $update_flag = wp_update_comment($commentarr);
            
            if($update_flag > 0){
                wp_send_json_success(array('msg' => 'Updated.','content' =>$commentarr['comment_content']));
            }else{
                wp_send_json_error(array('msg' => 'Error Updating.')); 
            }
          
        }
        
        /*
         * function to delete a comment
         * @param int $comment_id
         * @param bool true|false permanantly delete post 
         * 
         */
        public function delete_comment($comment_id,$force = false ){
 		$comment_array = get_comment( $comment_id, ARRAY_A );

		if ( empty( $comment_array ) ) {
			$resp = new WP_Error( 'json_comment_invalid_id', __( 'Invalid comment ID.' ), array( 'status' => 404 ) );
                        wp_send_json_error(array('msg'=>$resp->get_error_message()));
		}

		/*if ( ! current_user_can(  'edit_comment', $comment_array['comment_ID'] ) ) {
			$resp = new WP_Error( 'json_user_cannot_delete_comment', __( 'Sorry, you are not allowed to delete this comment.' ), array( 'status' => 401 ) );
                        wp_send_json_error(array('msg'=>$resp->get_error_message()));
		}*/

		$result = wp_delete_comment( $comment_array['comment_ID'], $force );

		if ( ! $result ) {
			$resp = new WP_Error( 'json_cannot_delete', __( 'The comment cannot be deleted.' ), array( 'status' => 500 ) );
                        wp_send_json_error(array('msg'=>$resp->get_error_message()));
		}

		if ( $force ) {
                    wp_send_json_success(array('msg'=>'Permanently deleted comment'));
		} else {
                    wp_send_json_success(array('msg'=>'Deleted comment'));
		}           
        }
        
        /*
         * function to get the comments for an object and its type
         * @param int $comment_obj_id 
         * @param string $comment_type configured comment type
         * @param bool $fetchall true to get all the records
         * 
         */
        public function get_comments($comment_obj_id,$comment_type,$fetchall = false){

            global $aj_commentsmodule;
            $limit = $offset = '';
            
            // check if custom comment type obj is registered
            if(! $aj_commentsmodule->is_registered_comment_type($comment_type)){
                wp_send_json_error(array('msg' => 'comment type not registered'));
            }
            
            // set the limit and offset to get the records
            if(! $fetchall){   
               $limit = isset($_REQUEST['limit'])? $_REQUEST['limit'] :2;
               $offset = isset($_REQUEST['offset'])? ( $_REQUEST['offset'] * $limit) :0;   
            }

            $defaults =  array(
                        'author_email' => '',
                        'ID' => '',
                        'karma' => '',
                        'number' => '',
                        'offset' => '',
                        'orderby' => '',
                        'order' => 'DESC',
                        'parent' => '',
                        'post_ID' => '',
                        'post_id' => 0,
                        'post_author' => '',
                        'post_name' => '',
                        'post_parent' => '',
                        'post_status' => '',
                        'post_type' => '',
                        'status' => '',
                        'type' => '',
                        'user_id' => '',
                        'search' => '',
                        'count' => false,
                        'meta_key' => '',
                        'meta_value' => '',
                        'meta_query' => '',
                        'date_query' => null,
                );            
            
            $args = array(
                    'post_id' => $comment_obj_id,
                    'type' =>$comment_type,
                    'offset' => $offset,
                    'number' => $limit,
                    'parent' => 0,
                    );
                        
            $args = wp_parse_args( $args, $defaults );
            $comments = get_comments($args); 
            $response['comments'] = $comments;
            
            $args['count'] = true;
            $comment_count =  get_comments($args); 
            $response['count'] = $comment_count;
               
            header('X-Total-Count: '.$comment_count);
            wp_send_json_success($response);
        }
        
        /*
         * function to get a comment replies
         * @param int $comment_id
         * @param bool $fetchall true to get all the records
         * 
         */
        public function get_replies($comment_id,$fetchall = false){
            
            $limit = $offset = '';
            
            $comment_array = get_comment( $comment_id, ARRAY_A );

                if ( empty( $comment_array ) ) {
                        $resp = new WP_Error( 'json_comment_invalid_id', __( 'Invalid comment ID.' ), array( 'status' => 404 ) );
                        wp_send_json_error(array('msg'=>$resp->get_error_message()));
                }
             
                // set the limit and offset to get the records
                if(! $fetchall){   
                   $limit = isset($_REQUEST['limit'])? $_REQUEST['limit'] :2;
                   $offset = isset($_REQUEST['offset'])? ( $_REQUEST['offset'] * $limit) :0;   
                }
             
             $defaults =  array(
                    'author_email' => '',
                    'ID' => '',
                    'karma' => '',
                    'number' => '',
                    'offset' => '',
                    'orderby' => '',
                    'order' => 'DESC',
                    'parent' => '',
                    'post_ID' => '',
                    'post_id' => 0,
                    'post_author' => '',
                    'post_name' => '',
                    'post_parent' => '',
                    'post_status' => '',
                    'post_type' => '',
                    'status' => '',
                    'type' => '',
                    'user_id' => '',
                    'search' => '',
                    'count' => false,
                    'meta_key' => '',
                    'meta_value' => '',
                    'meta_query' => '',
                    'date_query' => null,
            );
             
            $args = array(
                    'post_ID' =>$comment_array['comment_post_ID'],
                    'offset' => $offset,
                    'number' => $limit,
                    'parent' => $comment_id,
                    );
                        
            $args = wp_parse_args( $args, $defaults );             
            $comments = get_comments($args); 
            $response['comments'] = $comments;
            
            $args['count'] = true;
            $comment_count =  get_comments($args); 
            $response['count'] = $comment_count;
            
            header('X-Total-Count: '.$comment_count);
            wp_send_json_success($response);            
        }
        
    }

}
