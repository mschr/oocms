<?php

/**
 * @} End of "defgroup sanitization".
 */

/**
 * @defgroup format Formatting
 * @{
 * Functions to format numbers, strings, dates, etc.
 */

/**
 * Formats an RSS channel.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_channel($title, $link, $description, $items, $langcode = NULL, $args = array()) {
  global $language_content;
  $langcode = $langcode ? $langcode : $language_content->language;

  $output = "<channel>\n";
  $output .= ' <title>' . check_plain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $output .= ' <description>' . check_plain(decode_entities(strip_tags($description))) . "</description>\n";
  $output .= ' <language>' . check_plain($langcode) . "</language>\n";
  $output .= format_xml_elements($args);
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

/**
 * Format a single RSS item.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_item($title, $link, $description, $args = array()) {
  $output = "<item>\n";
  $output .= ' <title>' . check_plain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";
  $output .= ' <description>' . check_plain($description) . "</description>\n";
  $output .= format_xml_elements($args);
  $output .= "</item>\n";

  return $output;
}

/**
 * Format XML elements.
 *
 * @param $array
 *   An array where each item represents an element and is either a:
 *   - (key => value) pair (<key>value</key>)
 *   - Associative array with fields:
 *     - 'key': element name
 *     - 'value': element contents
 *     - 'attributes': associative array of element attributes
 *
 * In both cases, 'value' can be a simple string, or it can be another array
 * with the same format as $array itself for nesting.
 */
function format_xml_elements($array) {
  $output = '';
  foreach ($array as $key => $value) {
    if (is_numeric($key)) {
      if ($value['key']) {
        $output .= ' <' . $value['key'];
        if (isset($value['attributes']) && is_array($value['attributes'])) {
          $output .= drupal_attributes($value['attributes']);
        }

        if (isset($value['value']) && $value['value'] != '') {
          $output .= '>' . (is_array($value['value']) ? format_xml_elements($value['value']) : check_plain($value['value'])) . '</' . $value['key'] . ">\n";
        }
        else {
          $output .= " />\n";
        }
      }
    }
    else {
      $output .= ' <' . $key . '>' . (is_array($value) ? format_xml_elements($value) : check_plain($value)) . "</$key>\n";
    }
  }
  return $output;
}
?>
