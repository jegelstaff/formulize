<?php
			$label_class = null;
			$input_class = null;
			if (isset($ele->formulize_element)) {
				$label_class = " formulize-label-".$ele->formulize_element->getVar("ele_handle");
				$input_class = " formulize-input-".$ele->formulize_element->getVar("ele_handle");
			}
			if (!is_object($ele)) {// just plain add stuff if it's a literal string...
				if(strstr($ele, "<<||>>")) {
					$ele = explode("<<||>>", $ele);
					$ret .= "<tr id='formulize-".$ele[1]."'>".$ele[0]."</tr>";
				} elseif(substr($ele, 0, 3) != "<tr") {
					$ret .= "<tr>$ele</tr>";
				} else {
					$ret .= $ele;
				}
			} elseif ( !$ele->isHidden() ) {
				$ret .= "<tr id='formulize-".$ele->getName()."' class='".$ele->getClass()."' valign='top' align='" . _GLOBAL_LEFT . "'><td class='head$label_class'>";
				if (($caption = $ele->getCaption()) != '') {
					$ret .=
					"<div class='xoops-form-element-caption" . ($ele->isRequired() ? "-required" : "" ) . "'>"
						. "<span class='caption-text'>{$caption}</span>"
						. "<span class='caption-marker'>" . ($ele->isRequired() ? "*" : "" ) . "</span>"
						. "</div>";
				}
				if (($desc = $ele->getDescription()) != '') {
					$ret .= "<div class='xoops-form-element-help'>{$desc}</div>";
				}

                $ret .= "</td><td class='$class$input_class'>";
                if ($show_element_edit_link) {
                    $element_name = trim($ele->getName());
                    switch ($element_name) {
                        case 'control_buttons':
                        case 'proxyuser':
                            // Do nothing
                            break;

                        default:
                            if (is_object($ele) and isset($ele->formulize_element)) {
                                $ret .= "<a class=\"formulize-element-edit-link\" tabindex=\"-1\" href=\"" . XOOPS_URL .
                                    "/modules/formulize/admin/ui.php?page=element&aid=0&ele_id=" .
                                    $ele->formulize_element->getVar("ele_id") . "\" target=\"_blank\">edit element</a>";
                            }
                            break;
                    }
                }
                $ret .=  $ele->render()."</td></tr>\n";

			} else {
				$hidden .= $ele->render();
			}