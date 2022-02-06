<?php

/*
  Plugin Name: Chapter 4 - Book Reviews V10
  Plugin URI: 
  Description: Companion to recipe 'Adding filters for custom categories to the custom post list page'
  Author: ylefebvre
  Version: 1.0
  Author URI: http://ylefebvre.ca/
 */

/****************************************************************************
 * Code from recipe 'Creating a custom post type'
 ****************************************************************************/

add_action( 'init', 'ch4_br_create_book_post_type' );

function ch4_br_create_book_post_type() {
	register_post_type( 'book_reviews',
		array(
				'labels' => array(
				'name' => 'Book Reviews',
				'singular_name' => 'Book Review',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Book Review',
				'edit' => 'Edit',
				'edit_item' => 'Edit Book Review',
				'new_item' => 'New Book Review',
				'view' => 'View',
				'view_item' => 'View Book Review',
				'search_items' => 'Search Book Reviews',
				'not_found' => 'No Book Reviews found',
				'not_found_in_trash' => 'No Book Reviews found in Trash',
				'parent' => 'Parent Book Review',
			),
		'public' => true,
		'menu_position' => 20,
		'supports' => array( 'title', 'editor', 'comments', 'thumbnail' ),
		'taxonomies' => array( '' ),
		'menu_icon' => 'dashicons-book-alt',
		'has_archive' => false,
		'exclude_from_search' => false,
		)
	);
	
	/* Code from recipe 'Adding custom taxonomies for custom post types */    
	register_taxonomy(
		'book_reviews_book_type',
		'book_reviews',
		array(
			'labels' => array(
				'name' => 'Book Type',
				'add_new_item' => 'Add New Book Type',
				'new_item_name' => 'New Book Type Name',
			),
			'show_ui' => true,
			'meta_box_cb' => false,
			'show_tagcloud' => false,
			'hierarchical' => true,
		)
	);
}

/****************************************************************************
 * Code from recipe 'Adding a new section to the custom post type editor'
 ****************************************************************************/

// Register function to be called when admin interface is visited
add_action( 'admin_init', 'ch4_br_admin_init' );

// Function to register new meta box for book review post editor
function ch4_br_admin_init() {
	add_meta_box( 'ch4_br_review_details_meta_box', 'Book Review Details', 'ch4_br_display_review_details_mb', 'book_reviews', 'normal', 'high' );
}

// Function to display meta box contents
function ch4_br_display_review_details_mb( $book_review ) { 
	// Retrieve current author and rating based on book review ID
	$book_author = get_post_meta( $book_review->ID, 'book_author', true );
	$book_rating = get_post_meta( $book_review->ID, 'book_rating', true );
	?>
	<table>
		<tr>
			<td style="width: 150px">Book Author</td>
			<td><input type="text" style="width:100%" name="book_review_author_name" value="<?php echo esc_html( $book_author ); ?>" /></td>
		</tr>
		<tr>
			<td style="width: 150px">Book Rating</td>
			<td>
				<select style="width: 130px" name="book_review_rating">
					<option value="">Select rating</option>
					<!-- Loop to generate all items in dropdown list -->
					<?php for ( $rating = 5; $rating >= 1; $rating -- ) { ?>
					<option value="<?php echo intval( $rating ); ?>" <?php echo selected( $rating, $book_rating ); ?>><?php echo intval( $rating ); ?> stars
					<?php } ?>
				</select>
			</td>
		</tr>
		
		<!-- *********************************************************
			* Code from recipe 'Hiding the taxonomy editor from the
			* post editor while remaining in the admin menu'
			******************************************************* -->
		<tr>
			<td>Book Type</td>
			<td>
				<?php 

				// Retrieve array of types assigned to post
				$assigned_types = wp_get_post_terms( $book_review->ID, 'book_reviews_book_type' );
				
				// Retrieve array of all book types in system
				$book_types = get_terms( 'book_reviews_book_type', 
                                                         array( 'orderby' => 'name',
                                                                'hide_empty' => 0 ) );
				
				// Check if book types were found
				if ( $book_types ) {
					echo '<select name="book_review_book_type" style="width: 400px">';
					
					echo '<option value="">Select type</option>';

					// Display all book types
					foreach ( $book_types as $book_type ) {
						echo '<option value="' . intval( $book_type->term_id ) . '" ';
						if ( !empty( $assigned_types ) ) {
							selected( $assigned_types[0]->term_id, $book_type->term_id );
						}
						echo '>' . esc_html( $book_type->name ) . '</option>';
					}
					echo '</select>';
		} ?>
			</td>
		</tr>
	</table>

<?php }

// Register function to be called when posts are saved
// The function will receive 2 arguments
add_action( 'save_post', 'ch4_br_add_book_review_fields', 10, 2 );

function ch4_br_add_book_review_fields( $book_review_id, $book_review ) {
	if ( 'book_reviews' != $book_review->post_type ) {
		return;
	}

	if ( isset( $_POST['book_review_author_name'] ) ) {
		update_post_meta( $book_review_id, 'book_author', sanitize_text_field( $_POST['book_review_author_name'] ) );
	}
	if ( isset( $_POST['book_review_rating'] ) && !empty( $_POST['book_review_rating'] ) ) {
		update_post_meta( $book_review_id, 'book_rating', intval( $_POST['book_review_rating'] ) );
	}

	/*******************************************************************
	* Code from recipe 'Hiding the taxonomy editor from the post editor 
	* while remaining in the admin menu'
	*******************************************************************/

	if ( isset( $_POST['book_review_book_type'] ) ) {
		wp_set_post_terms( $book_review->ID, intval( $_POST['book_review_book_type'] ), 'book_reviews_book_type' );
	}
}

/************************************************************************************
 * Code from recipe 'Displaying single custom post type items using a custom layout'
 ************************************************************************************/

add_filter( 'template_include', 'ch4_br_template_include', 1 );

function ch4_br_template_include( $template_path ) {	
	if ( is_single() && 'book_reviews' == get_post_type()) {
		// checks if the file exists in theme first,
		// otherwise install content filter
		if ( $theme_file = locate_template( array( 'single-book_reviews.php' ) ) ) {
			return $theme_file;
		} else {
			add_filter( 'the_content', 'ch4_br_display_single_book_review',  20 );			
		}
	}
	return $template_path;
}

function ch4_br_display_single_book_review( $content ) {
    if ( empty( get_the_ID() ) ) {
		return;
	}

	// Display featured image in right-aligned floating div
	$content = '<div style="float: right; margin: 10px">';
	$content .= get_the_post_thumbnail( get_the_ID(), 'medium' );
	$content .= '</div>';
	
	$content .= '<div class="entry-content">';

	// Display Author Name
	$content .= '<strong>Author: </strong>';
	$content .= esc_html( get_post_meta( get_the_ID(), 'book_author', true ) );
	$content .= '<br />';

	// Display yellow stars based on rating -->
	$content .= '<strong>Rating: </strong>';

	$nb_stars = intval( get_post_meta( get_the_ID(), 'book_rating', true ) );

	$content .= str_repeat( '<img style="margin: 0" src="' . plugins_url( 'star-icon.png', __FILE__ ) . '" />', $nb_stars );
	$content .= str_repeat( '<img style="margin: 0" src="' . plugins_url( 'star-icon-grey.png', __FILE__ ) . '" />', 5 - $nb_stars );
	
	$book_types = wp_get_post_terms( get_the_ID(), 'book_reviews_book_type' ); 
 
	$content .= '<br /><strong>Type: </strong>';

	if ( $book_types ) {
		$type_array = array();
		foreach ( $book_types as $book_type ) {
			$type_array[] = $book_type->name;
		}
		$content .= esc_html( implode( ',', $type_array ) );
	} else {
		$content .= 'None Assigned';
	}

	// Display book review contents
	$content .= '<br /><br />' . get_the_content( get_the_ID() ) . '</div>';

	return $content;
}

/****************************************************************************
 * Code from recipe 'Tailoring search output for Custom Post Type items'
 ****************************************************************************/

add_filter( 'get_the_excerpt', 'ch4_br_search_display' );
add_filter( 'the_excerpt', 'ch4_br_search_display' );
add_filter( 'the_content', 'ch4_br_search_display' );

function ch4_br_search_display( $content ) {
    if ( !is_search() &&
            'book_reviews' != get_post_type() ) {
        return $content;
    }

    $content =
        '<div style="float: right; margin: 10px">'
        . get_the_post_thumbnail( get_the_ID(),
                                  'medium' )
        . '</div><div class="entry-content">'
        . '<strong>Author: </strong>'
        . esc_html( get_post_meta( get_the_ID(),
                          'book_author', true ) )
        . '<br /><strong>Rating: </strong>';

    $nb_stars = intval( get_post_meta( get_the_ID(),
                        'book_rating', true ) );
    $content .=
        str_repeat( '<img style="margin: 0" src="' . 
        plugins_url( 'star-icon.png', __FILE__ ) 
        . '" />', $nb_stars );
    $content .=
        str_repeat( '<img style="margin: 0" src="' . 
        plugins_url( 'star-icon-grey.png', __FILE__ ) 
        . '" />', 5 - $nb_stars );

    $content .= '<br /><br />';
    $content .= wp_trim_words(  
        get_the_content( get_the_ID() ), 20 );
    $content .= '</div>';
    return $content;
}


add_filter( 'the_title', 'ch4_br_review_title', 10, 2 );

function ch4_br_review_title( $title, $id = null ) {
    if ( !is_admin() && is_search() && !empty( $id ) ) { 
        $post = get_post( $id );
        if ( !empty( $post ) && $post->post_type == 'book_reviews' ) {            
            return 'Book review: ' . $title;
        }
    }
    return $title;
}

/****************************************************************************
 * Code from recipe 'Displaying custom post type data in shortcodes'
 ****************************************************************************/

add_shortcode( 'book-review-list', 'ch4_br_book_review_list' );

// Implementation of short code function
function ch4_br_book_review_list() {
	// Preparation of query array to retrieve 5 book reviews
	$query_params = array( 'post_type' => 'book_reviews',
                           'post_status' => 'publish',
                           'posts_per_page' => 5 );
	
	// Retrieve page query variable, if present
	$page_num = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	// If page number is higher than 1, add to query array
	if ( $page_num != 1 ) {
		$query_params['paged'] = $page_num;
	}

	// Execution of post query
	$book_review_query = new WP_Query;
    $book_review_query->query( $query_params );
	
	// Check if any posts were returned by query
	if ( $book_review_query->have_posts() ) {
		// Display posts in table layout
		$output = '<table>';
		$output .= '<tr><th style="text-align:left;"> ';
		$output .= '<strong>Title</strong></th>';
		$output .= '<th style="text-align:left">';
		$output .= '<strong>Author</strong></th></tr>';

		// Cycle through all items retrieved
		while ( $book_review_query->have_posts() ) {
			$book_review_query->the_post();
			$output .= '<tr><td style="padding-right: 20px">';
			$output .= '<a href="' . get_permalink();
			$output .= '">' . get_the_title( get_the_ID() );
			$output .= '</a></td><td>';
			$output .= esc_html( get_post_meta( get_the_ID(), 'book_author', true ) );
			$output .= '</td></tr>';
		}

		$output .= '</table>';

		// Display page navigation links
		if ( $book_review_query->max_num_pages > 1 ) {
			$output .= '<nav id="nav-below">';
			$output .= '<div class="nav-previous">';
			$output .= get_next_posts_link( '<span class="meta-nav">&larr;</span> Older reviews', $book_review_query->max_num_pages );
			$output .= '</div>';
			$output .= "<div class='nav-next'>";
			$output .= get_previous_posts_link( 'Newer reviews <span class="meta-nav">&rarr;</span>', $book_review_query->max_num_pages );
			$output .= '</div>';
			$output .= '</nav>';
		}

		// Reset post data query
		wp_reset_postdata();
	}

	return $output;
}

/****************************************************************************
 * Code from recipe 'Adding custom fields to categories'
 ****************************************************************************/

add_action( 'book_reviews_book_type_edit_form_fields', 'ch4_br_book_type_new_fields', 10, 2 );
add_action( 'book_reviews_book_type_add_form_fields', 'ch4_br_book_type_new_fields', 10, 2 );

function ch4_br_book_type_new_fields( $tag ) {
	$mode = 'new';
	
	if ( is_object( $tag ) ) {
		$mode = 'edit';
		$cat_color = get_term_meta( $tag->term_id, 'book_type_color', true );
	}
	$cat_color = empty( $cat_color ) ? '#' : $cat_color;

	if ( 'edit' == $mode ) {
		echo '<tr class="form-field">';
		echo '<th scope="row" valign="top">';
	} elseif ( 'new' == $mode ) {
		echo '<div class="form-field">';
	} ?>

	<label for="book_type_color">Color</label>
	<?php if ( 'edit' == $mode ) {
		echo '</th><td>';
	} ?>

	<input type="text" id="book_type_color" name="book_type_color" value="<?php echo $cat_color; ?>" />
	<p class="description">Color associated with book type (e.g. #199C27 or #CCC)</p>

	<?php if ( 'edit' == $mode ) {
		echo '</td></tr>';
	} elseif ( 'new' == $mode ) {
		echo '</div>';
	}
}

add_action( 'edited_book_reviews_book_type', 'ch4_br_save_book_type_new_fields', 10, 2 );
add_action( 'created_book_reviews_book_type', 'ch4_br_save_book_type_new_fields', 10, 2 );

function ch4_br_save_book_type_new_fields( $term_id, $tt_id ) {
	if ( !$term_id || !isset( $_POST['book_type_color'] ) ) {
		return;
	}

	if ( '#' == $_POST['book_type_color'] || preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $_POST['book_type_color'] ) ) {
		update_term_meta( $term_id, 'book_type_color', sanitize_text_field( $_POST['book_type_color'] ) );
	}
}

/****************************************************************************
 * Code from recipe 'Displaying additional columns in custom post list page'
 ****************************************************************************/

// Register function to be called when column list is being prepared
add_filter( 'manage_edit-book_reviews_columns', 'ch4_br_add_columns' );

// Function to add columns for author and type in book review listing
// and remove comments columns
function ch4_br_add_columns( $columns ) {
    $new_columns = array();
    $new_columns['book_reviews_author'] = 'Author';
    $new_columns['book_reviews_rating'] = 'Rating';
    $new_columns['book_reviews_type'] = 'Type';

    unset( $columns['comments'] );
    $columns = array_slice( $columns, 0, 2 ) + $new_columns + array_slice( $columns, 2 );
    
    return $columns;
}

// Register function to be called when custom post columns are rendered
add_action( 'manage_posts_custom_column', 'ch4_br_populate_columns' );

// Function to send data for custom columns when displaying items
function ch4_br_populate_columns( $column ) {
	if ( 'book_reviews' != get_post_type() ) {
		return;
    }

	// Check column name and send back appropriate data
	if ( 'book_reviews_author' == $column ) {
		echo esc_html( get_post_meta( get_the_ID(), 'book_author', true ) );
	}
	elseif ( 'book_reviews_rating' == $column ) {
		$rating = intval( get_post_meta( get_the_ID(), 'book_rating', true ) );
        if ( $rating > 0 ) {
            echo $rating . ' stars';
        } else {
            echo 'None Assigned';
        }
	}
	elseif ( 'book_reviews_type' == $column ) {
		$book_types = wp_get_post_terms( get_the_ID(), 'book_reviews_book_type' );

		if ( $book_types ) {
            $cat_color = get_term_meta( $book_types[0]->term_id, 'book_type_color', true );

            if ( !empty( $cat_color ) && '#' != $cat_color ) {
                echo '<span style="background-color: ' . esc_html( $cat_color );
                echo '; color: #fff; padding: 6px;">';
                echo esc_html( $book_types[0]->name ) . '</span>';
            } else {
                echo esc_html( $book_types[0]->name );
            }
        } else {
            echo 'None Assigned'; 
        }
	}
}

add_filter( 'manage_edit-book_reviews_sortable_columns', 'ch4_br_author_column_sortable' );

// Register the author and rating columns are sortable columns
function ch4_br_author_column_sortable( $columns ) {
	$columns['book_reviews_author'] = 'book_reviews_author';
	$columns['book_reviews_rating'] = 'book_reviews_rating';

	return $columns;
}

// Register function to be called when queries are being prepared to
// display post listing
add_filter( 'request', 'ch4_br_column_ordering' );

// Function to add elements to query variable based on incoming arguments
function ch4_br_column_ordering( $vars ) {
	if ( !is_admin() ) {
		return $vars;
	}
        
	if ( isset( $vars['orderby'] ) && 'book_reviews_author' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
				'meta_key' => 'book_author',
				'orderby' => 'meta_value'
		) );
	}
	elseif ( isset( $vars['orderby'] ) && 'book_reviews_rating' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
				'meta_key' => 'book_rating',
				'orderby' => 'meta_value_num'
		) );
	}

	return $vars;
}

/****************************************************************************
 * Code from recipe 'Adding filters for custom taxonomies to the custom
 * post list page'
 ****************************************************************************/

// Register function to be called when displaying post filter drop-down lists
add_action( 'restrict_manage_posts', 'ch4_br_book_type_filter_list' );

// Function to display book type drop-down list for book reviews
function ch4_br_book_type_filter_list() {
	$screen = get_current_screen();
	if ( 'book_reviews' != $screen->post_type ) {
		return;
	}

	global $wp_query;
	wp_dropdown_categories( array(
		'show_option_all' => 'Show All Book Types',
		'taxonomy' => 'book_reviews_book_type',
		'name' => 'book_reviews_book_type',
		'orderby' => 'name',
		'selected' => ( isset( $wp_query->query['book_reviews_book_type'] ) ? $wp_query->query['book_reviews_book_type'] : '' ),
		'hierarchical' => false,
		'depth' => 3,
		'show_count' => false,
		'hide_empty' => true,
	) );
}

// Register function to be called when preparing post query
add_filter( 'parse_query', 'ch4_br_perform_book_type_filtering' );

// Function to modify query variable based on filter selection
function ch4_br_perform_book_type_filtering( $query ) {
	$qv = &$query->query_vars;

	if ( isset( $qv['book_reviews_book_type'] ) &&
         !empty( $qv['book_reviews_book_type'] ) && 
         is_numeric( $qv['book_reviews_book_type'] ) ) {

			$term = get_term_by( 'id',$qv['book_reviews_book_type'],'book_reviews_book_type' );
			$qv['book_reviews_book_type'] = $term->slug;
    }
}