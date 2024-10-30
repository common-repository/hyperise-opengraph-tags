<?php
/*
* Plugin Name: HYPERISE Website Personalisation and OpenGraph personalised link previews
* Description: The plugin provides a simple way to add the Hyperise snippet to your website and start personalising your website. This plugin leverages Hyperise.com dynamic images to display personalised images in the preview pane, when you share a link with the utm_hyperef parameter added. Facebook OG tags into your blog's single posts which include Blog Title, Post Title, Description and Dynamic Image (if available). 
* Version: 2.8
* Author: HYPERISE
* Author URI: https://hyperise.com
* 
*/
error_reporting(0);

global $EnrichedData,$utm_hyperef; 

// Header Scripts
add_action('wp_head', 'hyperise_opengraphsingle');
add_action('add_meta_boxes', 'hyperise_add_post_meta_box', 1);
add_action('save_post', 'hyperise_save_postdata');
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );
remove_action('rest_api_init', 'wp_oembed_register_route');
remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);


if (isset($_GET['utm_hyperef']) && strlen($_GET['utm_hyperef'])) {
    //JetPack
    add_filter( 'jetpack_enable_open_graph', '__return_false' );
    
    // yeost
    add_filter( 'wpseo_title', 'add_keywords', 10, 1 );	        
    add_filter( 'wpseo_metadesc', 'add_desc', 10, 1 );
    add_filter( 'wpseo_opengraph_image', 'add_image', 10, 1 );	    
    add_filter( 'wpseo_canonical', 'add_url', 10, 1 );	        
    add_filter( 'wpseo_opengraph_url', 'add_url', 10, 1 );	        
    
    // rankmath https://rankmath.com/kb/filters-hooks-api-developer/
    add_filter( 'rank_math/frontend/title', 'add_keywords', 10, 1 );
    add_filter( 'rank_math/opengraph/facebook/image', 'add_image', 10, 1);
    add_filter( 'rank_math/opengraph/url', 'add_url', 10, 1);
    add_filter( 'rank_math/frontend/title', 'add_keywords', 10 ,1);
}

function add_url( $str ) {
    global $EnrichedData; 
    if($EnrichedData->utm_hyperef == '') {
        $EnrichedData=hyperise_enrichdata();
    }
    $EnrichedData->seoInstalled=true;
    return $str.'?utm_hyperef='.$EnrichedData->utm_hyperef;
}

function add_keywords( $str ) {
    global $EnrichedData; 
    if($EnrichedData->m_meta_title == '') {
        $EnrichedData=hyperise_enrichdata();
    }
    $EnrichedData->seoInstalled=true;
    return $EnrichedData->m_meta_title;
}

function add_desc( $str ) {
    global $EnrichedData; 
    if($EnrichedData->m_meta_desc == '') {
        $EnrichedData=hyperise_enrichdata();
    }
    $EnrichedData->seoInstalled=true;
    return $EnrichedData->m_meta_desc;
}

function add_image( $str ) {
    global $EnrichedData; 
    if($EnrichedData->image_url == '') {
        $EnrichedData=hyperise_enrichdata();
    }
    $EnrichedData->seoInstalled=true;
    return $EnrichedData->image_url;
}

function hyperise_save_postdata($post_id){
    if (array_key_exists('hyperise_field_hash', $_POST)) {
        update_post_meta(
            $post_id,
            '_hyperise_meta_hash',
            $_POST['hyperise_field_hash']
        );
    }
    if (array_key_exists('hyperise_field_title', $_POST)) {
        update_post_meta(
            $post_id,
            '_hyperise_meta_title',
            $_POST['hyperise_field_title']
        );
    }
    if (array_key_exists('hyperise_field_desc', $_POST)) {
        update_post_meta(
            $post_id,
            '_hyperise_meta_desc',
            $_POST['hyperise_field_desc']
        );
    }    
}

function hyperise_add_post_meta_box() {
    $post_types = get_post_types();
    
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'editor')) {
            add_meta_box(
                'hyperise_add_post',
                __( 'Hyperise Open Graph Settings' ),
                'hyperise_render_editor_box',
                $post_type
            );
        }
    }
}



function hyperise_render_editor_box( $post ) {
    $hash = get_post_meta($post->ID, '_hyperise_meta_hash', true);
    $title = get_post_meta($post->ID, '_hyperise_meta_title', true);
    $desc = get_post_meta($post->ID, '_hyperise_meta_desc', true);
    ?>
    <div id="hyperise-docx-uploader" class="status-empty">
        <table width="100%">
	<tr><td>Image Hash:</td><td><INPUT size="25" name="hyperise_field_hash" id="hyperise_field_hash" placeholder="" value="<?php echo $hash; ?>" /></td></tr>
	<tr><td>Title:</td><td><INPUT size="50" name="hyperise_field_title" id="hyperise_field_title" placeholder="{{first_name}} this is just for you" value="<?php echo $title; ?>" /></td></tr>
	<tr><td>Desc:</td><td><INPUT size="50" name="hyperise_field_desc" id="hyperise_field_desc" placeholder="Created just for {{business_name}}" value="<?php echo $desc; ?>" /></td></tr>
	</table>    
    </div>
<?php
}


function hyperise_enrichdata(){
    global $post,$EnrichedData,$utm_hyperef; 
    //$m_meta_description = get_post_meta($post->ID, '_hyperise_meta_key', true);
    
    $meta_image_hash = get_post_meta($post->ID, '_hyperise_meta_hash', true);

    if(strlen(trim($meta_image_hash))>0) {
        $meta_title = get_post_meta($post->ID, '_hyperise_meta_title', true);
        $meta_description = get_post_meta($post->ID, '_hyperise_meta_desc', true);            
        
        if(isset($_GET['utm_hyperef'])){
            $utm_hyperef=sanitize_text_field($_GET['utm_hyperef']);
        }else if(isset($_GET['email'])){
            $utm_hyperef=sanitize_email($_GET['email']);
        }
    
    	$image_template = $meta_image_hash;
        
        if(strlen($meta_title)>0){
            $page_title = $meta_title;
        }else{
            $page_title = $post->post_title;	
        }    
        
        if(strlen($meta_description)>0){
            $page_desc = $meta_description;
        }else{
            if( has_excerpt() ){ 
                $page_desc=get_the_excerpt();
            } else {
                $page_desc=substr(strip_tags($post->post_content),0,100);
            }        
        }    
        
        if(stristr($utm_hyperef,"_") && !stristr($utm_hyperef,"@")){
            $data_source_id=substr($utm_hyperef,0,strpos($utm_hyperef,"_"));
        }
    
        //Enrich the data based on Hyperise Ref...
        if(strlen($data_source_id)>0 && !stristr($utm_hyperef,"@")){
            //need to preg_match this...
            if(stristr($utm_hyperef,"_")){
                $row_id=substr($utm_hyperef,strpos($utm_hyperef,"_")+1);
            }else{
                $row_id=$utm_hyperef;
            }
            $json_data = wp_remote_get('https://hyperise.com/test/combinedapi.php?image_template='.$image_template.'&row_id='.$row_id.'&data_source_id='.$data_source_id);
            $image_url="https://img.hyperise.io/i/$image_template/sheet-$data_source_id/row-$row_id.png";
        }else{
            //need to preg_match this...
            $json_data = wp_remote_get('https://hyperise.com/test/image_api.php?image_template='.$image_template.'&utm_hyperef='.$utm_hyperef);
            $image_url="https://img.hyperise.io/i/$image_template.png?email=$utm_hyperef";
        } 
        $EnrichedData=json_decode($json_data['body']);
        $EnrichedData->data_source_id=$data_source_id;
        $EnrichedData->image_template=$image_template;
        $EnrichedData->image_url=$image_url;
        $EnrichedData->row_id=$row_id;
        $EnrichedData->imageID=$imageID;
        $EnrichedData->m_meta_snippet=get_option( 'hyperise-snippet' );
        $EnrichedData->m_meta_title=$page_title;       
        $EnrichedData->m_meta_title=str_replace("{{first_name}}",$EnrichedData->first_name,$EnrichedData->m_meta_title);       
        $EnrichedData->m_meta_title=str_replace("{{first_name}}",$EnrichedData->first_name,$EnrichedData->m_meta_title);
        $EnrichedData->m_meta_title=str_replace("{{last_name}}",$EnrichedData->last_name,$EnrichedData->m_meta_title);		
        $EnrichedData->m_meta_title=str_replace("{{job_title}}",$EnrichedData->job_title,$EnrichedData->m_meta_title);		
        $EnrichedData->m_meta_title=str_replace("{{business_name}}",$EnrichedData->business_name,$EnrichedData->m_meta_title);
        $EnrichedData->m_meta_title=str_replace("{{website}}",$EnrichedData->website,$EnrichedData->m_meta_title);
        $EnrichedData->m_meta_desc=$page_desc;
        $EnrichedData->m_meta_desc=str_replace("{{first_name}}",$EnrichedData->first_name,$EnrichedData->m_meta_desc);       
        $EnrichedData->m_meta_desc=str_replace("{{first_name}}",$EnrichedData->first_name,$EnrichedData->m_meta_desc);
        $EnrichedData->m_meta_desc=str_replace("{{last_name}}",$EnrichedData->last_name,$EnrichedData->m_meta_desc);		
        $EnrichedData->m_meta_desc=str_replace("{{job_title}}",$EnrichedData->job_title,$EnrichedData->m_meta_desc);		
        $EnrichedData->m_meta_desc=str_replace("{{business_name}}",$EnrichedData->business_name,$EnrichedData->m_meta_desc);
        $EnrichedData->m_meta_desc=str_replace("{{website}}",$EnrichedData->website,$EnrichedData->m_meta_desc);

        $EnrichedData->utm_hyperef=$utm_hyperef; 
    }else{
        $EnrichedData->m_meta_snippet=get_option( 'hyperise-snippet' );
    }
    return $EnrichedData;
}



function hyperise_opengraphsingle(){
    global $post,$EnrichedData; 

    if($EnrichedData->m_meta_title == '') {
        $EnrichedData=hyperise_enrichdata();
    }    	

    echo "<!-- HR-2.8: -->";
    echo $EnrichedData->m_meta_snippet;
    
    if ( is_single()  || is_page() ) {
    	    	    
    	if (isset($_GET['utm_hyperef']) || isset($_GET['email'])) {
    	    	    	
            //Replace the page content personalisation tags with enriched data...
            $content = $post->post_content;		
            $post->post_content=str_replace("{{utm_hyperef}}",$EnrichedData->utm_hyperef,$post->post_content);
            $post->post_content=str_replace("{{email}}",$EnrichedData->email,$post->post_content);
            $post->post_content=str_replace("{{first_name}}",$EnrichedData->first_name,$post->post_content);		
            $post->post_content=str_replace("{{last_name}}",$EnrichedData->last_name,$post->post_content);		
            $post->post_content=str_replace("{{profile_image}}",$EnrichedData->profile_url,$post->post_content);
            $post->post_content=str_replace("{{job_title}}",$EnrichedData->job_title,$post->post_content);		
            $post->post_content=str_replace("{{business_name}}",$EnrichedData->business_name,$post->post_content);	
            $post->post_content=str_replace("{{business_industry}}",$EnrichedData->business_industry,$post->post_content);		
            $post->post_content=str_replace("{{business_address}}",$EnrichedData->business_address,$post->post_content);
            $post->post_content=str_replace("{{business_lat}}",$EnrichedData->business_lat,$post->post_content);
            $post->post_content=str_replace("{{business_long}}",$EnrichedData->business_long,$post->post_content);
            $post->post_content=str_replace("{{business_phone}}",$EnrichedData->business_phone,$post->post_content);										
            $post->post_content=str_replace("{{logo}}",$EnrichedData->logo_url,$post->post_content);
            $post->post_content=str_replace("{{website}}",$EnrichedData->website,$post->post_content);
            $post->post_content=str_replace("{{website_screenshot}}",$EnrichedData->website_screenshot,$post->post_content);		
        
            $post->post_content=str_replace("{{custom_image_1}}",$EnrichedData->custom_image_1,$post->post_content);		
            $post->post_content=str_replace("{{custom_image_2}}",$EnrichedData->custom_image_2,$post->post_content);		
            $post->post_content=str_replace("{{custom_image_3}}",$EnrichedData->custom_image_3,$post->post_content);
            
            $post->post_content=str_replace("{{custom_text_1}}",$EnrichedData->custom_text_1,$post->post_content);
            $post->post_content=str_replace("{{custom_text_2}}",$EnrichedData->custom_text_2,$post->post_content);
            $post->post_content=str_replace("{{custom_text_3}}",$EnrichedData->custom_text_3,$post->post_content);				
            $post->post_content=str_replace("{{custom_text_4}}",$EnrichedData->custom_text_4,$post->post_content);		
            $post->post_content=str_replace("{{custom_text_5}}",$EnrichedData->custom_text_5,$post->post_content);		
                            
            $post->post_title=$EnrichedData->m_meta_title;
    
            ob_start();
            ob_end_clean();
            	        	
            if($EnrichedData->seoInstalled <> true AND $EnrichedData->image_template<>"" AND $_GET['utm_hyperef']<>""){
            	
                //Set the OpenGraph Tags, based on the personalised data we have...
                echo '<title>',$EnrichedData->m_meta_title,'</title>';
                echo "\n";
                echo '<meta name="description" content="',$EnrichedData->m_meta_desc,'">';
                echo "\n";
                echo '<meta property="og:site_name" content="',bloginfo('name'),'"/>';
                echo "\n";
                echo '<meta property="og:url" content="',the_permalink(),'?utm_hyperef=',$EnrichedData->utm_hyperef,'"/>';
                echo "\n";
                echo '<meta property="og:title" content="',$EnrichedData->m_meta_title,'"/>';
                echo "\n";
                echo '<meta property="og:description" content="',$EnrichedData->m_meta_desc,'"/>';
                echo "\n";
    	        echo '<meta property="og:image" content="',$EnrichedData->image_url,'"/>';
    	        echo "\n";
            }
        }
    }
}




// Settings Menu
add_action('admin_menu', 'hyperise_opengraphsingle_menu');
function hyperise_opengraphsingle_menu(){
    add_menu_page( 'HYPERISE OpenGraph/OG Tags', 'HYPERISE', 'administrator', 'fb-opengraph-tags', 'hyperise_opengraphsingle_menu_page', 'dashicons-image-filter', '50' );
}

add_action( 'admin_init', 'hyperise_opengraph_options' );

function hyperise_opengraph_options(){
    register_setting( 'meta-data', 'hyperise-snippet' );
}

function hyperise_opengraphsingle_menu_page(){ ?>
    <div class="wrap">
	<h1 class="ogtitle">Hyperise website content and OpenGraph personalisation setup</h1>
	<form class="ogdata" action="options.php" method="post">
	<?php settings_fields( 'meta-data' ); ?>
	<?php do_settings_sections( 'hyperise_opengraphsingle_menu' ); ?>
	<h3>Hyperise Snippet</h3>	
	<p class="ogpara">Add your Hyperise Snippet below.  If you're unsure where to get your Hyperise snippet from, checkout the <a href="https://support.hyperise.com/website-personalisation/1-adding-hyperise-snippet-to-your-website" target="_blanks">Adding the Hyperise snippet to your website Guide</a>.</p>
	<textarea id="w3review" name="hyperise-snippet" rows="5" cols="100">
	<?php echo  get_option( 'hyperise-snippet' ); ?>
	</textarea>

    <p>To enable OpenGraph personalisation, edit the post/page and set the title, description and image.</p>

	<?php submit_button(); ?>
	</form>
	<p class="ogpara">To start making your personalized images, create a free account with <a href="https://hyperise.com" target="_blank">hyperise.com</a></p>
    </div>
    <?php
}

// Load Styles
function hyperise_load_styles(){
    wp_register_style( 'ogstyles', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', false, 'v.1.1' );
    wp_enqueue_style( 'ogstyles' );
}
add_action( 'admin_enqueue_scripts', 'hyperise_load_styles' );

?>