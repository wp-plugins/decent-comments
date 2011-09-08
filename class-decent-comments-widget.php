<?php
/**
 * class-decent-comments-widget.php
 * 
 * Copyright (c) 2011 "kento" Karim Rahimpur www.itthinx.com
 * 
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 * 
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * This header and all notices must be kept intact.
 * 
 * @author Karim Rahimpur
 * @package decent-comments
 * @since decent-comments 1.0.0
 * @link http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */

/**
 * Versatile comments widget.
 */
class Decent_Comments_Widget extends WP_Widget {
	
	/**
	 * @var string cache id
	 */
	static $cache_id = 'decent_comments_widget';
	
	/**
	 * @var string cache flag
	 */
	static $cache_flag = 'widget';
	
	/**
	 * Initialize class.
	 */
	static function init() {
		if ( !has_action( 'wp_print_styles', array( 'Decent_Comments_Widget', '_wp_print_styles' ) ) ) {
			add_action( 'wp_print_styles', array( 'Decent_Comments_Widget', '_wp_print_styles' ) );
		}
		if ( !has_action( 'comment_post', array( 'Decent_Comment_Widget', 'cache_delete' ) ) ) {
			add_action( 'comment_post', array( 'Decent_Comment_Widget', 'cache_delete' ) );
		}
		if ( !has_action( 'transition_comment_status', array( 'Decent_Comment_Widget', 'cache_delete' ) ) ) {
			add_action( 'transition_comment_status', array( 'Decent_Comment_Widget', 'cache_delete' ) );
		}
	}
	
	/**
	 * Creates a Decent Comments widget.
	 */
	function Decent_Comments_Widget() {
		parent::WP_Widget( false, $name = 'Decent Comments' );
	}
	
	/**
	 * Clears cached comments.
	 */
	static function cache_delete() {
		wp_cache_delete( self::$cache_id, self::$cache_flag );
	}
	
	/**
	 * Enqueue styles if at least one widget is used.
	 */
	static function _wp_print_styles() {
		global $wp_registered_widgets, $DC_version;
		foreach ( $wp_registered_widgets as $widget ) {
			if ( $widget['name'] == 'Decent Comments' ) {
				wp_enqueue_style( 'decent-comments-widget', DC_PLUGIN_URL . 'css/decent-comments-widget.css', array(), $DC_version );
				break;
			}
		}
	}
	
	/**
	 * Widget output
	 * 
	 * @see WP_Widget::widget()
	 */
	function widget( $args, $instance ) {
				
		// Note that there won't be any efficient caching unless a persistent
		// caching mechanism is used. WordPress' default cache is persistent
		// during a request only so this won't have any effect on our widget
		// unless it were printed twice on a page - and that won't happen as
		// each widget is different and cached by its own id.
		// @see http://codex.wordpress.org/Class_Reference/WP_Object_Cache
		$cache = wp_cache_get( self::$cache_id, self::$cache_flag );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];
			return;
		}
		
		extract( $args );

		$title = apply_filters( 'widget_title', $instance['title'] );
		
		$widget_id = $args['widget_id'];
		
		// output
		$output = '';
		$output .= $before_widget;
		if ( !empty( $title ) ) {
			$output .= $before_title . $title . $after_title;
		}
		$output .= Decent_Comments_Renderer::get_comments( $instance );
		$output .= $after_widget;
		echo $output;
		
		$cache[$args['widget_id']] = $output;
		wp_cache_set( self::$cache_id, $cache, self::$cache_flag );
	}
		
	/**
	 * Save widget options
	 * 
	 * @see WP_Widget::update()
	 */
	function update( $new_instance, $old_instance ) {
		$settings = $old_instance;
		
		// title
		$settings['title'] = strip_tags( $new_instance['title'] );
		
		// number		
		$number = intval( $new_instance['number'] );
		if ( $number > 0 ) {
			$settings['number'] = $number;
		}
		
		// orderby
		$orderby = $new_instance['orderby'];
		if ( key_exists( $orderby, Decent_Comments_Renderer::$orderby_options ) ) {
			$settings['orderby'] = $orderby;
		} else {
			unset( $settings['orderby'] );
		}
		
		// order
		$order = $new_instance['order'];
		if ( key_exists( $order, Decent_Comments_Renderer::$order_options ) ) {
			$settings['order'] = $order;
		} else {
			unset( $settings['order'] );
		}
		
		// post_id
		$post_id = $new_instance['post_id'];
		if ( empty( $post_id ) ) {
			unset( $settings['post_id'] );
		} else if ("[current]" == $post_id ) {
			$settings['post_id'] = "[current]";
		} else if ( $post = get_post( $post_id ) ) {
			$settings['post_id'] = $post->ID;
		} else if ( $post = Decent_Comments_Helper::get_post_by_title( $post_id ) ) {
			$settings['post_id'] = $post->ID;
		}
		
		// excerpt
		$settings['excerpt'] = !empty( $new_instance['excerpt'] );
		
		// max_excerpt_words
		$max_excerpt_words = intval( $new_instance['max_excerpt_words'] );
		if ( $max_excerpt_words > 0 ) {
			$settings['max_excerpt_words'] = $max_excerpt_words;
		}
		
		// ellipsis
		$settings['ellipsis'] = strip_tags( $new_instance['ellipsis'] );
		
		// show_author
		$settings['show_author'] = !empty( $new_instance['show_author'] );
		
		// show_avatar
		$settings['show_avatar'] = !empty( $new_instance['show_avatar'] );
		
		// avatar_size
		$avatar_size = intval( $new_instance['avatar_size'] );
		if ( $avatar_size > 0 ) {
			$settings['avatar_size'] = $avatar_size;
		}
		
		// show_link
		$settings['show_link'] = !empty( $new_instance['show_link'] );
		
		// show_comment
		$settings['show_comment'] = !empty( $new_instance['show_comment'] );

		$this->cache_delete();
		
		return $settings;
	}
	
	/**
	 * Output admin widget options form
	 * 
	 * @see WP_Widget::form()
	 */
	function form( $instance ) {
		
		extract( Decent_Comments_Renderer::$defaults );
		
		// title
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = "";
		}
		echo "<p>";
		echo '<label for="' .$this->get_field_id( 'title' ) . '">' . __( 'Title' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';
		
		// number
		if ( isset( $instance['number'] ) ) {
			$number = intval( $instance['number'] );
		}
		echo "<p>";
		echo '<label class="title" title="' . __( "The number of comments to show.", DC_PLUGIN_DOMAIN ) .'" for="' .$this->get_field_id( 'number' ) . '">' . __( 'Number of comments' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'number' ) . '" name="' . $this->get_field_name( 'number' ) . '" type="text" value="' . esc_attr( $number ) . '" />';
		echo '</p>';
		
		// orderby
		if ( isset( $instance['orderby'] ) ) {
			$orderby = $instance['orderby'];
		}
		echo '<p>';
		echo '<label class="title" title="' . __( "Sorting criteria.", DC_PLUGIN_DOMAIN ) .'" for="' .$this->get_field_id( 'orderby' ) . '">' . __( 'Order by ...' ) . '</label>';
		echo '<select class="widefat" name="' . $this->get_field_name( 'orderby' ) . '">';
		foreach ( Decent_Comments_Renderer::$orderby_options as $orderby_option_key => $orderby_option_name ) {
			$selected = ( $orderby_option_key == $orderby ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . $orderby_option_key . '">' . $orderby_option_name . '</option>'; 
		}
		echo '</select>';
		echo '</p>';
		
		// order
		if ( isset( $instance['order'] ) ) {
			$order = $instance['order'];
		}
		echo '<p>';
		echo '<label class="title" title="' . __( "Sort order.", DC_PLUGIN_DOMAIN ) .'" for="' .$this->get_field_id( 'order' ) . '">' . __( 'Sort order' ) . '</label>';
		echo '<select class="widefat" name="' . $this->get_field_name( 'order' ) . '">';
		foreach ( Decent_Comments_Renderer::$order_options as $order_option_key => $order_option_name ) {
			$selected = ( $order_option_key == $order ? ' selected="selected" ' : "" );
			echo '<option ' . $selected . 'value="' . $order_option_key . '">' . $order_option_name . '</option>'; 
		}
		echo '</select>';
		echo '</p>';
		
		// post_id
		$post_id = '';
		if ( isset( $instance['post_id'] ) ) {
			if ( '[current]' == strtolower( $instance['post_id'] ) ) {
				$post_id = '[current]';
			} else {
				$post_id = $instance['post_id'];
			}
		}
		echo "<p>";
		echo '<label class="title" title="' . __( "Leave empty to show comments for all posts. To show comments for a specific post only, indicate either part of the title or the post ID. To show posts for the current post, indicate: [current]", DC_PLUGIN_DOMAIN ) . '" for="' .$this->get_field_id( 'post_id' ) . '">' . __( 'Post ID' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'post_id' ) . '" name="' . $this->get_field_name( 'post_id' ) . '" type="text" value="' . esc_attr( $post_id ) . '" />';
		echo '<br/>';
		echo '<span class="description">' . __( "Title, empty, post ID or [current]", DC_PLUGIN_DOMAIN ) . '</span>';
		if ( !empty( $post_id ) && ( $post_title = get_the_title( $post_id ) ) ) {
			echo '<br/>';
			echo '<span class="description"> ' . sprintf( __("Selected post: <em>%s</em>", DC_PLUGIN_DOMAIN ) , $post_title ) . '</span>';
		}
		echo '</p>';
        
		// excerpt
		$checked = ( ( ( !isset( $instance['excerpt'] ) && Decent_Comments_Renderer::$defaults['excerpt'] ) || ( $instance['excerpt'] === true ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'excerpt' ) . '" />';
		echo '<label class="title" title="' . __( "If checked, shows an excerpt of the comment. Otherwise the full text of the comment is displayed.", DC_PLUGIN_DOMAIN ) .'" for="' . $this->get_field_id( 'excerpt' ) . '">' . __( 'Show comment excerpt', DC_PLUGIN_DOMAIN ) . '</label>';
		echo '</p>';
		
		// max_excerpt_words
		if ( isset( $instance['max_excerpt_words'] ) ) {
			$max_excerpt_words = intval( $instance['max_excerpt_words'] );
		}
		echo "<p>";
		echo '<label class="title" title="' . __( "The maximum number of words shown in excerpts.", DC_PLUGIN_DOMAIN ) .'" for="' .$this->get_field_id( 'max_excerpt_words' ) . '">' . __( 'Number of words in excerpts' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'max_excerpt_words' ) . '" name="' . $this->get_field_name( 'max_excerpt_words' ) . '" type="text" value="' . esc_attr( $max_excerpt_words ) . '" />';
		echo '</p>';
		
		// ellipsis
		if ( isset( $instance['ellipsis'] ) ) {
			$ellipsis = $instance['ellipsis'];
		}
		echo "<p>";
		echo '<label class="title" title="' . __( "The ellipsis is shown after the excerpt when there is more content.", DC_PLUGIN_DOMAIN ) . '" for="' .$this->get_field_id( 'ellipsis' ) . '">' . __( 'Ellipsis' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'ellipsis' ) . '" name="' . $this->get_field_name( 'ellipsis' ) . '" type="text" value="' . esc_attr( $ellipsis ) . '" />';
		echo '</p>';

		// show_author
		$checked = ( ( ( !isset( $instance['show_author'] ) && Decent_Comments_Renderer::$defaults['show_author'] ) || ( $instance['show_author'] === true ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'show_author' ) . '" />';
		echo '<label class="title" title="' . __( "Whether to show the author of each comment.", DC_PLUGIN_DOMAIN ) .'" for="' . $this->get_field_id( 'show_author' ) . '">' . __( 'Show author', DC_PLUGIN_DOMAIN ) . '</label>';
		echo '</p>';
		
		// show_avatar
		$checked = ( ( ( !isset( $instance['show_avatar'] ) && Decent_Comments_Renderer::$defaults['show_avatar'] ) || ( $instance['show_avatar'] === true ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'show_avatar' ) . '" />';
		echo '<label class="title" title="' . __( "Show the avatar of the author.", DC_PLUGIN_DOMAIN ) .'" for="' . $this->get_field_id( 'show_avatar' ) . '">' . __( 'Show avatar', DC_PLUGIN_DOMAIN ) . '</label>';
		echo '</p>';

		// avatar size
		if ( isset( $instance['avatar_size'] ) ) {
			$avatar_size = intval( $instance['avatar_size'] );
		}
		echo "<p>";
		echo '<label class="title" title="' . __( "The size of the avatar in pixels.", DC_PLUGIN_DOMAIN ) .'" for="' .$this->get_field_id( 'avatar_size' ) . '">' . __( 'Avatar size' ) . '</label>'; 
		echo '<input class="widefat" id="' . $this->get_field_id( 'avatar_size' ) . '" name="' . $this->get_field_name( 'avatar_size' ) . '" type="text" value="' . esc_attr( $avatar_size ) . '" />';
		echo '</p>';
		
		// show_link
		$checked = ( ( ( !isset( $instance['show_link'] ) && Decent_Comments_Renderer::$defaults['show_link'] ) || ( $instance['show_link'] === true ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'show_link' ) . '" />';
		echo '<label class="title" title="' . __( "Show a link to the post that the comment applies to.", DC_PLUGIN_DOMAIN ) .'" for="' . $this->get_field_id( 'show_link' ) . '">' . __( 'Show link to post', DC_PLUGIN_DOMAIN ) . '</label>';
		echo '</p>';

		// show_comment
		$checked = ( ( ( !isset( $instance['show_comment'] ) && Decent_Comments_Renderer::$defaults['show_comment'] ) || ( $instance['show_comment'] === true ) ) ? 'checked="checked"' : '' );
		echo '<p>';
		echo '<input type="checkbox" ' . $checked . ' value="1" name="' . $this->get_field_name( 'show_comment' ) . '" />';
		echo '<label class="title" title="' . __( "Show an excerpt of the comment or the full comment.", DC_PLUGIN_DOMAIN ) .'" for="' . $this->get_field_id( 'show_comment' ) . '">' . __( 'Show the comment', DC_PLUGIN_DOMAIN ) . '</label>';
		echo '</p>';
		
		// @todo render widget if no comments? option 
	}
}// class Decent_Comments_Widget

Decent_Comments_Widget::init();
register_widget( 'Decent_Comments_Widget' );
?>