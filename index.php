<?php
 /* Convert Word Documents to HTML
 *
 *  Cleans up the otherwise-ugly output generated by Microsoft Word when you use the File -> Save As HTML option.
 *  and optionally includes Twitter Bootstrap. Relies heavily on WordPress's texturize engine and internal encoding
 *  functions to do the heavy lifting. Also converts footnotes into a format compatible with Bootstrap's tooltips
 * 
 *  Copyright (C) 2011-2012  Benjamin J. Balter  ( ben@balter.com -- http://ben.balter.com )
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright 2012
 *  @license GPL v3 or later
 *  @version 1.1
 *  @package word-to-html
 *  @author Benjamin J. Balter <ben@balter.com>
 */

//WordPress (path to WordPress install's wp-load file)
//The only configuration you really need to worry about
$path_to_wordpress = '../3.3.2/wp-load.php';

//no file uploaded, present form
if ( empty( $_FILES ) ) {
	include 'form.php';
	exit();
}

//bootstrap a few things we're gonna need...
include 'tags.php'; //custom KSES file
include 'functions.php'; //grab custom footnote parsing function

//verify we have WordPress to bootstrap
if ( !file_exists( $path_to_wordpress ) )
	die( 'Please specify a pass to your local WordPress install\'s wp-load.php file at the top of index.php' );
	
include $path_to_wordpress; 

//attempt to read temporary file into a string
$html = @file_get_contents( $_FILES['file']['tmp_name'] );

//if something doesn't look right, no need to go any further
if ( $_FILES['file']['error'] || $_FILES['file']['type'] != 'text/html' || !$html )
	die( 'Invalid Upload' );

$bootstrap = isset( $_POST['bootstrap'] );

//Use WordPress's native filter API since we're already bootstrapped... 
//You can add or remove any filters you want here

add_filter( 'convert_word', 'bb_normalize_encoding'     ); //normalize encoding to UTF-8 (Word Mac gives us Western)
add_filter( 'convert_word', 'wp_kses_post'              ); //strip extraneous tags and attributes
add_filter( 'convert_word', 'convert_chars'             ); //clean up encoding, HTML entities, etc.
add_filter( 'convert_word', 'wptexturize'               ); //Typset all the things
add_filter( 'convert_word', 'force_balance_tags'        ); //Ensure we get Clean, valid HTML back
add_filter( 'convert_word', 'bb_parse_footnotes'        ); //Convert footnotes into someting usable
add_filter( 'convert_word', 'bb_strip_comments'         ); //Sometimes Word Comments out styles depending on export options
add_filter( 'convert_word', 'bb_remove_extra_spaces'    ); //remove any consecutive spaces 
add_filter( 'convert_word', 'bb_remove_empty_ps'        ); //After all that, we may end up with extraneous p tags
add_filter( 'convert_word', 'bb_normalize_line_endings' ); //Word gives us Windows line endings, even on mac... shocker
add_filter( 'convert_word', 'bb_remove_hard_word_wrap'  ); //remove hard word wrap best we can
add_filter( 'convert_word', 'bb_b_to_strong'            ); //what is this, 1999? Who uses <b> and <i> still?
add_filter( 'convert_word', 'bb_i_to_em'                );

//convert file and output straight back to browser as download
header('Content-type: text/html');
header('Content-Disposition: attachment; filename="' . $_FILES['file']['name'] . '"');

if ( $bootstrap )
	echo file_get_contents( 'templates/header.html' );
	
echo apply_filters( 'convert_word', $html );

if ( $bootstrap )
	echo file_get_contents( 'templates/footer.html' );

exit();