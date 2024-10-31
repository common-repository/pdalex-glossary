<?php
/*
Plugin Name: PdAlex Glossary
Plugin URI: http://pdalex.org.ua/?page_id=254
Description: This plugin creates good glossary for your WordPress powered site.РЏ)
Version: 1.3.1
Last updated time: 2011-1-12 
Author: Alexey Poddybniy
Author URI: http://pdalex.org.ua


  Copyright 2010  Alexey Poddybniy  (email: alexey.poddybniy@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

global $jal_db_version;
global $wpdb;
global $table_name;

$jal_db_version = "1.0";
$table_name =  $wpdb->prefix . "gloss";

function gloss_install () {
   global $wpdb;
   global $jal_db_version;
   global $table_name;


   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
	  `id` mediumint(11) NOT NULL auto_increment,
	  `termin` varchar(100) NOT NULL,
	  `dess` text NOT NULL,
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
 
      add_option("jal_db_version", $jal_db_version);
      add_option("pdalex_glossary_col", "1");
   }
}

function pdalex_get_title ($title) {
	global $wpdb;
        global $table_name;

	$gloss_id = htmlspecialchars(str_replace("_"," ",str_replace("'","",$_GET['termin'])));
			
	$arTerminOne = $wpdb->get_results (pdalex_select_termin_id($table_name, $gloss_id));

	foreach ($arTerminOne AS $one){$termin_t = $one->termin;}	


	return $termin_t . " | ".$title;
}
function pdalex_lang_detect ($string) {

	$string = mb_strtolower($string, 'UTF-8');

	 $_langs = array(
           'en'=>array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'),
           'ru'=>array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'),
           );
	
	foreach ($_langs as $lang_code=>$lang_name)
	 {
	       $lang_res[$lang_code] = 0; //по умолчанию 0, то есть не этот язык
    
	       $tmp_freq = 0; // частота символов текущего языка
	       $full_lang_symbols = 0; //полное количество символов этого языка

   	       $cur_lang = $_langs[$lang_code];
           
	       foreach ($cur_lang as $l_item)
	       	{
			$tmp_term = mb_substr_count($string, $l_item);
		        if ($tmp_term >= 1) // то есть символ в строе 1 или более раз
		         {
		           $tmp_freq++; // увеличим счетчик символов языка, которые в этой строке есть
	        	   $full_lang_symbols += $tmp_term;
		         }
	        }

	         $lang_res[$lang_code] = ceil((100 / count($cur_lang) ) * $tmp_freq);       

         }
     // так, теперь посомтрим что вышло
	     arsort($lang_res, SORT_NUMERIC); //сортируем массив первый элемент язык с большей вероятностью

         $key = key($lang_res);
         
         return $key;

     
}
function pdalex_display_glossary() {
	global $wpdb;
	global $wp_query;
        global $table_name;

	$the_url = get_bloginfo('url');
	$count_column = get_option("pdalex_glossary_col");
	if ($count_column == "") $count_column = 1;

       if ($_GET['termin'] != "" ) 
        {

		$gloss_id = htmlspecialchars(str_replace("_"," ",str_replace("'","",$_GET['termin'])));
			
		$arTerminOne = $wpdb->get_results (pdalex_select_termin_id($table_name, $gloss_id));
        }
	else {
		$arTermin = $wpdb->get_results (pdalex_select_termin ($table_name));
	}

	if ($_GET['termin'] != "")
	 {
		foreach ($arTerminOne AS $one){
	 	$p_out = "<h1>".$one->termin."</h1>";
		$p_out .= "<p>".$one->dess."</p>";
		$p_out .= "<p><a href=".$the_url . "?page_id=" . $wp_query->post->ID.">&laquo; Back</a></p>";
		}
	 }
	else
	if (count($arTermin)>0)
	 {
		$p_out =  "<div style=\"float:left\"><ul>";
		$i = 0;
		$count_pos = count ($arTermin);
		$pdalex_column = ceil ($count_pos/$count_column);
		$first_letter_old = "&&";
		$first_letter = "";
		foreach ($arTermin AS $termin)
		 {
			if ($pdalex_column == $i) { $p_out .= "</ul></div><div style=\"float:left\"><ul>"; $i=0;}

			$pdalex_lang = pdalex_lang_detect ($termin->termin);

			if ($pdalex_lang == "ru") 
				$first_letter = substr($termin->termin,0,2);//[0].$termin->termin[1].$termin->termin[2].$termin->termin[3].$termin->termin[4].$termin->termin[5].$termin->termin[6].$termin->termin[7];
			else
				$first_letter = $termin->termin[0];

			if ($first_letter != $first_letter_old) {
				$p_out .= "<Br><b style=\"font-size:15px;\">".mb_strtoupper ($first_letter,'UTF-8')."</b><br><hr>";
			}
			$p_out .= "<li><a href=\"" . $the_url . "?page_id=" . $wp_query->post->ID . "&amp;termin=".str_replace(" ","_",$termin->termin)."\" title=\"".$termin->termin."\">".$termin->termin."</a></li>";

			$i++;
			$first_letter_old = $first_letter;
	 	}
		$p_out .= "</ul></div><div class=\"cleared\"></div>";
		$p_out .= "<div align=\"right\"><a href=\"http://www.pdalex.org.ua\" target=\"_blank\" style=\"font-size:8px;\">PdAlex&nbsp;Glossary</a></div>";
		$p_out .= "<div class=\"cleared\" style=\"clear: both;\"></div><br>";
	 }

	return $p_out;

}
function pdalex_select_termin($table_name)
  {
                        $query = ' select termin,id from '.$table_name.' order by termin ;';
                        return $query;
  }
function pdalex_select_termin_id($table_name,$id)
   {
                        $query = ' select * from '.$table_name.' where termin = \''.$id.'\' ;';
                        return $query;
    }      	

/* 
 * Display glossary
 */
function pdalex_gloss_create($content) {
	if (strpos($content, "<!-- pdalex_glossary -->") !== FALSE) {
		$content = preg_replace('/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content);
		$content = str_replace('<!-- pdalex_glossary -->', pdalex_display_glossary(), $content);
	}
	elseif (strpos($content, "{pdalex_glossary}") !== FALSE) {
		$content = str_replace('{pdalex_glossary}', pdalex_display_glossary(), $content);	
	}
	return $content;
}

/*
 * admin page
*/
function pdalex_admin_gloss (){
	
	add_menu_page ('Glossary Page','Browse glossary', 8, __FILE__,'pdalex_view_gloss');
	add_submenu_page (__FILE__,'Add new term','Add new term',8, 'form-gloss','pdalex_add_gloss'); 
	add_submenu_page (__FILE__,'Options','Options',8, 'option-gloss','pdalex_option_gloss'); 
}

function pdalex_option_gloss () {

	if ($_POST['action'] == "update")
	 {
		update_option("pdalex_glossary_col", $_POST['pdalex_glossary_col']);
	 }

	$count_column = get_option("pdalex_glossary_col");
	if ($count_column == "") $count_column = 1;	 
?>
	<div class="wrap">
	<h2>Options PdAlex Glossary</h2>
	<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<?php wp_nonce_field('update-options'); ?>
	<table class="form-table">

<tr valign="top">
<th scope="row">Number of columns<br><small>Specify on how many columns you want to divide the list of terms</small></th>
<td><input type="text" name="pdalex_glossary_col" value="<?php echo $count_column;?>" /></td>
</tr>


	</table>
<input type="hidden" name="action" value="update" />
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>

<?
}
function pdalex_view_gloss(){
	global $wpdb;
	global $wp_query;
        global $table_name;

	if ($_GET['action'] == "delete")
	 {
		$gloss_id = esc_html(str_replace("_"," ",str_replace("'","",$_GET['termin'])));
		$wpdb->query ($wpdb->prepare( "DELETE from ".$table_name." where termin = %s;", $gloss_id));

		$add = "deleted";
	 }

	$arTermin = $wpdb->get_results (pdalex_select_termin ($table_name));

echo "<div style=\"width:70%;float:left;\">";
	if ($add == true)
		echo "<div id=\"message\" class=\"updated fade below-h2\"><p>Term successfull deleted</p></div><br>";
?>
<h2>Browse glossary <a href="admin.php?page=form-gloss" class="button add-new-h2"><?php _e('Add new term', 'mt_trans_domain' )?></a></h2>
<p>
For install plugin Insert {pdalex_glossary} on the page mode html where you want to see a glossary
</p>
<div id="namediv" class="stuffbox">
<h3 style="font-size:12px;font-weight:bold;margin:0;padding:7px 9px;"><label for="link_name"><?php _e('View terms') ?></label></h3>
<div class="inside">

<?
	$countTerm = count($arTermin);
	if ($countTerm>0)
	 {
		$twoC = ceil ($countTerm/2);
		$ii = 0;
		echo  "<table border=\"1\" style=\"border-bottom:1px solid black\">";
		foreach ($arTermin AS $termin)
		 {
			if ($ii == 2){$bgcolor_r="#ccc";$ii=0;}else{$bgcolor_r="#bbb";$ii++;}
			echo "<tr bgcolor=\"".$bgcolor_r."\"><td width=\"30px\">".$termin->id."</td><td> <a href=\"admin.php?page=form-gloss&amp;termin=".str_replace(" ","_",$termin->termin)."&amp;action=edit\" title=\"".$termin->termin."\">".$termin->termin."</a></td><td><td> <a href=\"admin.php?page=form-gloss&amp;termin=".str_replace(" ","_",$termin->termin)."&amp;action=edit\" title=\"".$termin->termin."\">Edit</a></td><td><a href=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "&amp;termin=".str_replace(" ","_",$termin->termin)."&amp;action=delete\" title=\"Delete\">Delete</a></td></tr>\n";
	 	 }
		echo "</table>";
	 }
?>
</div>
<?
echo "</div>";

}
/*function pdalex_option_gloss () {
	global $wpdb;
	global $wp_query;
        global $table_name;

}*/
function pdalex_add_gloss(){
	global $wpdb;
	global $wp_query;
        global $table_name;

	$field_termin = "field_termin";
	$field_dess = "field_dess";
	$field_id = "field_id";

	$add = false;
	
	if ($_GET['action'] == "edit")
	 {
		$gloss_id = esc_html(str_replace("_"," ",str_replace("'","",$_GET['termin'])));

		$arTerminOne = $wpdb->get_results (pdalex_select_termin_id($table_name,$gloss_id));

		foreach ($arTerminOne AS $one)
		 {
			$val_id = $one->id;
			$val_termin = $one->termin;
			$val_dess = $one->dess;
		 }

		$add = "Edit";
	 }
	else $add = "Add";

	echo "<div style=\"width:70%;float:left;\">";

	if (count($_POST)>0)
	 {
	if ($_POST[$field_id] != "")
	 {
		$termin = esc_html ($_POST[$field_termin]);
		$dess = esc_html ($_POST[$field_dess]);
		$termin_id = esc_html ($_POST[$field_id]);


		if ( false === $wpdb->update( $table_name, array( 'termin' => $termin, 'dess' => $dess ), array ('id' => $termin_id ) )) {
			if ( $wp_error )
				return new WP_Error( 'db_insert_error', __( 'Could not update termin into the database' ), $wpdb->last_error );
			else
				return 0;
		}

		$add = "Editing";

		echo "<div id=\"message\" class=\"updated fade below-h2\"><p>Term successfully edited</p></div><br>";
	 }
	else
	 {
		$termin = esc_html ($_POST[$field_termin]);
		$dess = esc_html ($_POST[$field_dess]);

	

		if ( false === $wpdb->insert( $table_name, array( 'termin' => $termin, 'dess' => $dess ) ) ) {
			if ( $wp_error )
				return new WP_Error( 'db_insert_error', __( 'Could not insert termin into the database' ), $wpdb->last_error );
			else
				return 0;
		}
		$termin_id = (int) $wpdb->termin_id;

		$add = "Adding";

		echo "<div id=\"message\" class=\"updated fade below-h2\"><p>Term successfully added</p></div><br>";		
	 }
	 }


?>
	<div class="wrap">
	<div id="icon-link-manager" class="icon32"><br></div>
<?
	echo "<h2>$add Term <a href=\"admin.php?page=".__FILE__."\" class=\"button add-new-h2\">Browse glossary</a></h2>";
?>
	</div>
	<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type=hidden name="<?php echo $field_id; ?>" value="<?php echo $val_id; ?>" />

<div id="namediv" class="stuffbox">
<h3 style="font-size:12px;font-weight:bold;margin:0;padding:7px 9px;"><label for="link_name"><?php _e('Term') ?></label></h3>
<div class="inside">
	<input type="text" id="link_name" style="width:98%;" name="<?php echo $field_termin; ?>" size="30" tabindex="1" value="<?php echo $val_termin; ?>" />
    <p><?php _e('Example: SEO'); ?></p>
</div>
</div>

<div  class="stuffbox">
<h3 style="font-size:12px;font-weight:bold;margin:0;padding:7px 9px;"><label for="link_name"><?php _e('Description') ?></label></h3>
<div class="inside">
	<textarea style="width:98%;" name="<?php echo $field_dess; ?>"><?php echo $val_dess; ?></textarea>
	<p><?php _e('Write an explanation of the term'); ?></p>
</div>
</div>
	<hr />

	<p class="submit">
	<div style="float:left;"><input type="submit" class="button-primary" name="Submit" value="<?php _e($add.' Termin', 'mt_trans_domain' ) ?>" /></div>
	<div style="float:right;"><input type="button"  name="Back" onClick="location.href='admin.php?page=gloss.php'" value="<?php _e('Back', 'mt_trans_domain' ) ?>" /></div>
	</p>


	</form>
<?
	echo "</div>";

}

/*******/

register_activation_hook(__FILE__,'gloss_install');

//add_filter('wp_title', 'pdalex_get_title', $wp_query->post->ID ); 
add_filter('single_post_title', 'pdalex_get_title', $wp_query->post->ID ); 

add_filter('the_content', 'pdalex_gloss_create');


add_action ('admin_menu', 'pdalex_admin_gloss');


?>