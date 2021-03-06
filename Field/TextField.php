<?php
namespace NeuroSYS\DoctrineDatatables\Field;

class TextField extends AbstractField {
    public function getSearch() {
        $text = parent::getSearch();
        $text = str_replace(['\\', '_', '%', '*'], ['\\\\', '\\_', '\\%', '%'], $text);
        return strlen($text) > 0 ? '%' . $text . '%' : '';
    }
}
