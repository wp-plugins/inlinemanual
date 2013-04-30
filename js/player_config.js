// jQuery( function() {
// 	IMP.config().routes.topics = '?feed=inlinemanual';
// 	IMP.config().routes.topic = '?feed=inlinemanual&inm_topic=';
// 	IMP.start();
// });
jQuery(document).ready(function($){
	$config = json_encode(
      array(
          // 'tour' => array('basePath' => $safe_base_path),
          'tour' => array('basePath' => '/'),
          'routes' => array(
            'topics' => url('?feed=inlinemanual'),
            'topic' => url('?feed=inlinemanual&inm_topic=')
          )
      )
    );
};