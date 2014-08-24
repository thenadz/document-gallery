<?php

/**
 * The schema that the DG options structure must match.
 */
global $options_schema;
$options_schema = array (
      'thumber' => array (
            'thumbs',
            'gs',
            'active',
            'width',
            'height',
            'timeout'
      ),
      'gallery' => array (
            'attachment_pg',
            'descriptions',
            'fancy',
            'ids',
            'images',
            'localpost',
            'order',
            'orderby',
            'relation' 
      ),
      'css' => array (
            'text',
            'last-modified',
            'etag',
            'version' 
      ),
      'version',
      'validation',
      'logging'
);