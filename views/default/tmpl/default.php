<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_helloworld
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die;
JHtml::_('formbehavior.chosen', 'select');
?>



<form action="index.php?option=com_dcmeta" method="post" id="adminForm" name="adminForm">

<p>This page helps you see the meta info for each article and menu, with links to easily edit those items. Some tips:</p>
<ul>
	<li>Search enginges will typically truncate meta descriptions over 160 characters long (see text length counters below).</li>
	<li>Search enginges typically now ignore meta keywords, but they will be used in internal joomla searches.</li>
	<li>The article Browser Page Title is found at bottom of article options tab, but (unless you modify your template) it is only used when the article has no menu associated with it.  So unless you modify your template, in order to set the browser page title for an article assigned to a menu you need to set the MENU page title.</li>
	<li>By default this page only shows menus NOT assigned to articles; you can change that in the options for this extension.</li>
	<li>It is recommended that you leave meta description and keywords for Menus that are for articles blank, and set that info in the article; browser page title is the only exception, since by default it is only the menu browser page title that is used.</li>
</ul>

<br/>


<?php
	$edit_mode = true && $this->dcmetaviewer->get_edit_mode();
	$metadesc_maxlen = 160;
	$use_maxlen_js = true;

	$articles = $this->dcmetaviewer->get_articles();
	echo '<h2 class="dcmetatable">ARTICLE META INFO</h2>' . "\n";

	echo '
	<table class="dcmetatable">
	<thead>
		<tr>';
		
		$columns = $this->dcmetaviewer->get_article_columns();
		foreach ($columns as $key => $label) {
			echo '<th>' . $label . '</th>' . "\n";
			}

	echo '
	</tr>
	</thead>
	<tbody>
	';
	
	
	foreach ($articles as $article) {
		
		echo '<tr>' . "\n";
		foreach ($columns as $key => $label) {
			// class
			$classes = 'dcmetacola_' . $key;
			//
			if ($key == 'title') {
				$url = 'index.php?option=com_content&task=article.edit&id=' . $article->id . '#publishing';
				$val = '<a href="' . $url . '" target="_blank">' . $article->title . '</a>';
			} else if  ($key == 'path') {
				$path = $article->path;
				if (strpos($path,'?')!==false) {
					$classes .= ' dcpathnomenu';
				} else {
					$classes .= 'dcpathmenu';
				}
				$val = '<a href="' . $article->url_view . '" target="_blank">' . $path . '</a>';
			} 
			else {
				// generic field
				$val = $article->$key;
			}

			if (!$edit_mode && $key=='metadesc') {
				if (strlen($val)>$metadesc_maxlen) {
						$classes .= ' dcmi_toolong';
				}
			}
			
			// editable
			if ($edit_mode) {
				if ($key=='metadesc') {
					$editclass='longarea';
					$classes .= ' dcmimaxlen';
				} else if ($key=='metakey') {
					$editclass='shortarea';
				} else if ($key=='article_page_title') {
					$editclass='short';
				}	else {
					$editclass='';
				}
				//
				if (!empty($editclass)) {
					$classes .= ' dcmetainput_' . $editclass;
					$varname = 'a' . $article->id . '_' . $key;
					if ($editclass=='longarea' || $editclass=='shortarea') {
						$val = '<textarea name="' . $varname . '">' . $val . '</textarea>';
					} else if ($editclass=='short') {
						$val = '<input type="text" name="' . $varname . '"  value="' . htmlentities($val) . '">';
					} 
				}
			}

			echo '<td class="' . $classes . '">' . $val . '</td>' . "\n";
		}

		echo '</tr>' . "\n";
	}


	echo '</tbody>
	</table>
	';


//	echo '<pre>' . htmlentities(print_r($articles,true)) . '</pre>';
?>



<?php
	$menus = $this->dcmetaviewer->get_menus();
	echo "<br/><hr/><br/>\n";
	echo '<h2 class="dcmetatable">MENU META DATA</h2>' . "\n";

	echo '
	<table class="dcmetatable">
	<thead>
		<tr>';
		
		$columns = $this->dcmetaviewer->get_menu_columns();
		foreach ($columns as $key => $label) {
			echo '<th>' . $label . '</th>' . "\n";
			}

	echo '
	</tr>
	</thead>
	<tbody>
	';


	$hidemenus = $this->dcmetaviewer->get_hidemenus();

	foreach ($menus as $menu) {

		// first decide whether hidden
		if (strpos($menu->link,'com_content&view=article')!==false) {
			$hidden = true;
		} else if ($menu->type=='url') {
			$hidden = true;
		} else if ($menu->type=='heading') {
			$hidden = true;
		}	else {
			$hidden = false;
		}
		//
		if ($hidemenus && $hidden) {
			continue;
		}


		echo '<tr>' . "\n";

		foreach ($columns as $key => $label) {
			// class
			$classes = 'dcmetacolm_' . $key;
			//
			if ($key == 'title') {
				$url = 'index.php?option=com_menus&task=item.edit&id=' . $menu->id . '#attrib-metadata';
 				$val = '<a href="' .$url . '" target="_blank">' . $menu->title . '</a>';
			} else if ($key=='notes') {
				if (strpos($menu->link,'com_content&view=article')!==false) {
					$val = 'Article link; prefer meta info in article';
				} else if ($menu->type=='url') {
					$val = 'URL link; meta info not accessible';
				} else if ($menu->type=='heading') {
					$val = 'Menu heading; meta info not accessible';
				}
				else {
					$val = '';
				}
			}
			else if ($key=='menulink') {
				$mltext = $menu->link;
				$mltext = htmlentities($mltext);
				$mltext = str_replace('&','<wbr>&',$mltext);
				$mltext = str_replace('?','<wbr>?',$mltext);
				//$mlurl = $menu->link;
				$mlurl = $baseurl = JURI::root() . $menu->path;
				$val = '<a href="' . $mlurl . '" target="_blank">' .  $mltext . '</a>';
				$val = $mltext;
			}
			else if ($key=='path') {
				$url = $baseurl = JURI::root() . $menu->path;
				$val = '<a href="' . $url . '" target="_blank">' .  $menu->path . '</a>';
			}
			else {
				// generic field
				$val = $menu->$key;
			}


			if (!$edit_mode && $key=='meta_description') {
				if (strlen($val)>$metadesc_maxlen) {
						$classes .= ' dcmi_toolong';
				}
			}

			// editable
			if ($edit_mode) {
				if ($key=='meta_description') {
					$editclass='longarea';
					$classes .= ' dcmimaxlen';
				} else if ($key=='meta_keywords') {
					$editclass='shortarea';
				} else if ($key=='page_title') {
					$editclass='short';
				} else {
					$editclass='';
				}
				if (!empty($editclass)) {
					$classes .= ' dcmetainput_' . $editclass;
					$varname = 'm' . $menu->id . '_' . $key;
					if ($editclass=='longarea' || $editclass=='shortarea') {
						$val = '<textarea name="' . $varname . '">' . $val . '</textarea>';
					} else if ($editclass=='short') {
						$val = '<input type="text" name="' . $varname . '" value="' . htmlentities($val) . '">';
					}
				}
			}
				
			echo '<td class="' . $classes . '">' . $val . '</td>' . "\n";
		}

		echo '</tr>' . "\n";
	}
	
	echo '</tbody>
	</table>
	';

	//echo '<pre>' . htmlentities(print_r($menus,true)) . '</pre>';


	if ($edit_mode) {
		echo '
		<br/><br/>
				<button type="submit" class="btn btn-primary">
					Submit All Changes
				</button>
		';

  if ($use_maxlen_js) {
		echo '<script>
		jQuery( document ).ready(function() {
        jQuery(\'.dcmimaxlen textarea\').characterCounter({maximumCharacters:' . $metadesc_maxlen . ', shortFormat: true, characterCounterNeeded:true});
	    });
		</script>';
	  }

	}



?>





<input type="hidden" name="task" value=""/>
<?php echo JHtml::_('form.token'); ?>
</form>

