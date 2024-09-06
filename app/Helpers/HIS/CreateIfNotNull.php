<?php 

if (!function_exists('createIfNotNull')) {
    function createIfNotNull($model, array $data) {
        if ($model) {
            return $model->create($data);
        }
        return false;
    }
}