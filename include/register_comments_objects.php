<?php
/*
 * Custom functions of registering Comments types
 * 
 * function to register comment type
 * @param string $comment_type comment type name 
 * 
 */
function register_comments_types($comment_type = ''){
    global $ajcm_comment_types;
    
    $ajcm_comment_types = array();
    //get the hooked Document types and assign to global variable
    $ajcm_comment_types = apply_filters('ajcm_comment_types_filter',$ajcm_comment_types);
    if($comment_type != ''){
        if(empty($ajcm_comment_types)){
            $ajcm_comment_types[] = $comment_type;
        }else{
            if(!in_array($comment_type, $ajcm_comment_types))
                    $ajcm_comment_types[] = $comment_type;
        }
    }
}

/*
 * hook function to get the theme defined comment object types
 */
function theme_defined_comment_types($ajcm_comment_types){
    $defined_comment_types = array();  // theme defined comment types array  ie format array('requests','events')
    $defined_comment_types = apply_filters('add_comment_types_filter',$defined_comment_types);
    
    foreach($defined_comment_types as $comment_type){
            if(!in_array($comment_type, $ajcm_comment_types))
                $ajcm_comment_types[] = $comment_type;
    }

    return $ajcm_comment_types;
    
}
add_filter('ajcm_comment_types_filter','theme_defined_comment_types',10,1);
