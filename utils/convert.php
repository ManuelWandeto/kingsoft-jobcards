<?php

function convertToBytes($value) {
    $limit = trim(strtolower($value), 'mkg');
    $last = strtolower($value[strlen($value)-1]);
  
    switch($last) {
      case 'g':
        $limit *= 1000000000;
        break;
      case 'm':
        $limit *= 1000000;
        break;
      case 'k':
        $limit *= 1000;
        break;
    }
    return $limit;
  }