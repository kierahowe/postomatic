<?php 
/**
 * @package Post-O-Matic
 * @version 1.0
 */
/*
Plugin Name: Post-O-Matic
Plugin URI: http://www.kierahowe.com
Description: outputs a list of posts based on certain selectable criteria
Author: Kiera Howe
Version: 1.0
Author URI: http://www.kierahowe.com
*/

class postomatic_MySettingsPage
{
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_init' ) );
    }

    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Post-O-Matic Settings', 
            'manage_options', 
            'my-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'pomoptions' );
        ?>
        <div class="wrap">
            <h1>Post-O-Matic Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );
                do_settings_sections( 'my-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_init () { 
    	wp_enqueue_media(); 
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {       
    	 
        register_setting(
            'my_option_group', // Option group
            'pomoptions', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Default Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'my-setting-admin' // Page
        );  

        add_settings_field(
            'def_image_id', // ID
            'Default Image', // Title 
            array( $this, 'def_image_id_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_section_id' // Section           
        );      

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['def_image_id'] ) )
            $new_input['def_image_id'] = absint( $input['def_image_id'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function def_image_id_callback()
    {
    			
    	$post_thumbnail_id = isset( $this->options['def_image_id'] ) ? esc_attr( $this->options['def_image_id']) : '';
    	if ($post_thumbnail_id != "")  {
			$img = wp_get_attachment_image_src($post_thumbnail_id)[0];
		}	
		?><img id="imgitem" height="150" src="<?php echo $img; ?>"><?php 
		?>
    	<div class="uploader">
    		<input type="hidden" id="def_image_id" name="pomoptions[def_image_id]" value="<?php echo $post_thumbnail_id; ?>">
			<input id="_unique_name_button" class="button" name="_unique_name_button" type="text" value="Select Image" />
		</div>
<script>
jQuery(document).ready(function($){
	var _custom_media = true;
	var _orig_send_attachment = wp.media.editor.send.attachment;

	$('#_unique_name_button').click(function(e) {
		var send_attachment_bkp = wp.media.editor.send.attachment;
		var button = $(this);
		var id = button.attr('id').replace('_button', '');
		_custom_media = true;
		wp.media.editor.send.attachment = function(props, attachment){
			$("#def_image_id").val(attachment.id);
			document.getElementById ('imgitem').src = attachment.url;
		}

		wp.media.editor.open();
		return false;
	});

	$('.add_media').on('click', function(){
		_custom_media = false;
	});
});
</script>


    	<?php 
        
    }
}

if( is_admin() )
    $my_settings_page = new postomatic_MySettingsPage();


function postomatic_postsearch ($atts) { 
	$args = array (
		'taxonomy' => 'category',
		"child_of" => $atts['cat'],
		"hide_empty" => false,
	); 
	$categories = get_terms($args);

	$defsel = explode (",", strtolower($atts['defaultselect']));

	$out = "";
	ob_start ();
	
?>
	<div class="pomsearch">
		<div class="pomfilters<?php echo (($atts['nofilters'])?" nofilters":""); ?>" onClick="showfilter();">
			<i class="fa fa-filter" aria-hidden="true"></i> Filter
		</div>
		<div class="pomtext<?php echo (($atts['nofilters'])?" nofilters":""); ?>">
			<label>
				<span class="screen-reader-text">Search for:</span>
				<input type="search" class="search-field" placeholder="Search..." value="" name="s" id="pomstext">
			</label>
			<input type="submit" class="search-submit" value="Search" onClick="pomDoUpdate(0);">
		</div>
		<div style="clear: both;"></div>

		<div class="pomfilterpopup<?php echo (($atts['nofilters'])?" nofilters":""); ?>" id="pomfilterpopup">
			<?php

			// $args = array (
			// 	"show_count" => '1',
			// 	"hierarchical" => '1',
			// 	"hide_empty" => '0',
			// 	"id" => esc_attr( $this->get_field_id( 'category' ) ), 
			// 	"name" => esc_attr( $this->get_field_name( 'category' ) ),
			// 	"selected" => $category
			// 	); 
			
			//wp_dropdown_categories($args); 

			$args = array("parent" => 29, "hide_empty" => false);
			$cats = get_categories ($args);
			$outcat = "";
			foreach ($cats as $n => $v) { 
			?>
				<div id="pomtopcat_<?php echo $v->term_id; ?>" class="pomtopcat" onClick="dotopcat(<?php echo $v->term_id; ?>);">
					<?php print "" . $v->name . "<br>"; ?>
				</div>
			<?php 
				$args = array("parent" => $v->term_id);
				$subcats = get_categories ($args);
				$outcat .= "<div id=\"pomsubcat_" . $v->term_id . "\" class=\"pomsubcat\">";
				foreach ($subcats as $nn => $vv) { 
					$sel = ((   in_array(strtolower($vv->name), $defsel) || in_array($vv->term_id, $defsel)	)?"CHECKED":"");
					$outcat .= "<input type=checkbox ". $sel . " id=\"tag_" . $vv->term_id . "\" onChange=\"pomDoUpdate(0);\"> " . $vv->name . "<br>"; ?>
			<?php 
				}
				$outcat .= "</div>";
			}
			print $outcat;

			?>
		</div>
	</div>
	<div id="pomsearchcontent" class="pomsearchcontent<?php echo (($atts['fullwidth'])?" fullwidth":""); ?>">
		<div id="pomsearchcontentitems" class="pomsearchcontent<?php echo (($atts['fullwidth'])?" fullwidth":""); ?>">
		</div>
		<div style="clear: both;">
		</div>
	</div>

	<script>

	function dotopcat (num) { 
		var cl = document.getElementsByClassName("pomsubcat");

		for(var i = 0; i < cl.length; i++) {
			if(cl[i].id == "pomsubcat_" + num){ 
				cl[i].style.display = "block";
				document.getElementById('pomtopcat_' + num).style.backgroundColor = "#dddddd";				
			} else { 
				cl[i].style.display = "none";
				var idnum = cl[i].id.substring(10);
				document.getElementById('pomtopcat_' + idnum).style.backgroundColor = "white";				
			}  
		}
	}
	<?php if (count ($cats) == 1) { 
		print "dotopcat (" . $cats[0]->term_id . ");";
	} ?>

	function showfilter () { 
		document.getElementById("pomfilterpopup").style.display =  
			(document.getElementById("pomfilterpopup").style.display == "" || document.getElementById("pomfilterpopup").style.display == "none")
			?"block":"none";
	}


	var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	var page = 1;
	var updating = 0;

	function pomDoUpdate(nxpage) { 
		var search = document.getElementById('pomstext').value;
		var inputs = document.getElementsByTagName("input");
		var tags = [];
		var d = document.getElementById('pomsearchcontentitems');

		if (updating == 1) { return; }
		updateing = 1;
		if (nxpage == 0) { 
			d.innerHTML = "";
			page = 1;
		} else { 
			page += nxpage;
		}

		for(var i = 0; i < inputs.length; i++) {
			if(inputs[i].type == "checkbox" && inputs[i].id.substring(0, 4) == 'tag_' 
				&& inputs[i].checked == true) { 
				tags.push (parseInt(inputs[i].id.substring(4)));
			}  
		}


		var data = {
			'action': 'getcatitems',
			'search': search,
			'tags': JSON.stringify(tags),
			'page': page
		};

		//d.innerHTML = "wait";
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			d.innerHTML += response;
			updating = 0;
		});
	}

	jQuery(document).ready(function() {
		var win = jQuery(window)
		win.scroll (function () { 
			if (jQuery(document).height() - win.height() <= win.scrollTop() + 30) {
				pomDoUpdate(1);
			}
		});
	});

	pomDoUpdate(0);
	</script>
<?php 
	$out = ob_get_clean();
	
	return $out;
}


add_shortcode('postsearch', 'postomatic_postsearch');


function postomatic_menucats ($atts) { 
	$atts = shortcode_atts( array('cat' => '', 'icons' => false), $atts);
	if (!is_numeric ($atts['cat']) || $atts['cat'] == "") { return ""; }
	
	$args = array (
		'taxonomy' => 'category',
		"child_of" => $atts['cat'],
		"hide_empty" => false
	); 
	$categories = get_terms($args);

	$out = "";
	ob_start ();
?>
	<div class="catsmenu">
		<?php
			foreach ($categories as $n => $v) { 
			?>
			<div class="<?php echo (($atts['icons'])?"caticons":"catitem"); ?>" 
				style="width: <?php echo 100 / count($categories); ?>%; <?php
					if ($atts['icons']) { 
						print "background-image: url(" . get_option('z_taxonomy_image'.$v->term_id) . ");";
					}
				?>">
				<?php if ($atts['icons']) { 
					print "<div class=\"caticonmain\">";
					print ((substr($v->description, 0, 9) == "dashicons")?"<span class=\"dashicons " . $v->description . "\"></span>":$v->description);
					print "</div>"; 
				} ?>
				<div class="itemlink"><a href="javascript: getCat(<?php echo $v->term_id ?>);"><?php echo $v->name ?></a></div>
			</div>
			<?php 
			}
		?>
	</div>
	<div id="catsmenucontent" class="catsmenucontent">
	</div>
	
	<script>
	var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

	function getCat (id) { 
		var data = {
			'action': 'getcatitems',
			'id': id
		};

		var d = document.getElementById('catsmenucontent');
		d.innerHTML = "wait";
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			d.innerHTML = response;
		});
	}

	</script>
<?php 
	$out = ob_get_clean();
	
	return $out;
}


add_shortcode('menucats', 'postomatic_menucats');


add_action( 'wp_ajax_getcatitems', 'postomatic_getcatitems' );
add_action( 'wp_ajax_nopriv_getcatitems', 'postomatic_getcatitems' );
function postomatic_getcatitems() {
	global $wpdb;
	$id = intval( $_POST['id'] );
	$search = strip_tags($_POST['search']);
	$tags = json_decode($_POST['tags'], true);
	$page = intval( $_POST['page'] );
	if ($page == 0) { $page = 1; }
	// /print $_POST['tags'];
	$args = array ("post_type" => "post", "posts_per_page" => 9, 'paged' => $page);
	if ($id != "") { $args["cat"] = $id;  }
	if ($tags != "" && count($tags)>0) { 
		$args['cat'] = $tags;
	}
	if ($search != "") { $args["s"] = $search;  }

	$the_query = new WP_Query( $args );

	$out = array ();
	if ( $the_query->have_posts() ) {
		while ($the_query->have_posts()) { 
			$the_query->the_post();

			$post_thumbnail_id = get_post_thumbnail_id();
			$img = wp_get_attachment_image_src($post_thumbnail_id)[0];

			if ($img == "") { 
				$post_thumbnail_id = get_option('pomoptions', '' )['def_image_id'];
				$img = wp_get_attachment_image_src($post_thumbnail_id)[0];
			}
			//print_r (get_option('pomoptions', '' ));
?>
			<div class="postitem">
				<div class="postpic" style="background-image: url(<?php echo $img; ?>);">
				</div>
				<div class="postpreview">
					<?php 
					print "<h4><a href=\"" . get_permalink() . "\">" . get_the_title() . "</a></h4>";
					echo "<div class=\"pomexcerpt\">" . postomatic_excerpt (23) . "</div>";

					?>
				</div>
				<?php 
					print "<p><a href=\"" . get_permalink() . "\"><i class=\"fa fa-circle\" aria-hidden=\"true\"></i><i class=\"fa fa-circle\" aria-hidden=\"true\"></i><i class=\"fa fa-circle\" aria-hidden=\"true\"></i></a></p>";
				?>
			</div>
		<?php
		}
	}

	wp_reset_postdata();
	wp_die();
}

function postomatic_excerpt($limit) {
  $excerpt = explode(' ', get_the_excerpt(), $limit);

  if (count($excerpt)>=$limit) {
	array_pop($excerpt);
	$excerpt = implode(" ",$excerpt).'...';
  } else {
	$excerpt = implode(" ",$excerpt);
  }	
  $excerpt = preg_replace('`[[^]]*]`','',$excerpt);
  return $excerpt;
}


function postomatic_menupages ($atts) { 

	$atts = shortcode_atts( array('page' => '', 'icons' => false), $atts);
	if (!is_numeric ($atts['page']) || $atts['page'] == "") { return ""; }
	
	$pages = array();
	$args = array ("post_type" => "page", "posts_per_page" => 10, "post_parent" => $atts['page']);
	$the_query = new WP_Query( $args );

	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();

			$o = array ("description" => get_the_excerpt (), "link" => get_permalink(), "title" => get_the_title ());
			$pages[] = $o;
		}
	}


	wp_reset_postdata();

	$out = "";
	ob_start ();
?>
	<div class="catsmenu">
		<?php
			foreach ($pages as $n => $v) { 
			?>
			<div class="<?php echo (($atts['icons'])?"caticons":"catitem"); ?>" 
				style="width: <?php echo 100 / count($pages); ?>%; <?php
					if ($atts['icons']) { 
						print "background-image: url(" . wp_get_attachment_image_src($instance['thumbnail'])[0] . ");";
					}
				?>">
				<?php if ($atts['icons']) { 
					print "<div class=\"caticonmain\">";
					print ((substr($v['description'], 0, 9) == "dashicons")?"<span class=\"dashicons " . $v['description'] . "\"></span>":$v->description);
					print "</div>"; 
				} ?>
				<div class="itemlink"><a href="<?php echo $v['link']; ?>"><?php echo $v['title'] ?></a></div>
			</div>
			<?php 
			}
		?>
	</div>
	<div style="clear: both"></div>
	
<?php 
	$out = ob_get_clean();
	
	return $out;
}


add_shortcode('menupages', 'postomatic_menupages');
