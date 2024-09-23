<?php 

if (!function_exists('updateIfNotNull')) {
    function updateIfNotNull($model, array $data) {
        if ($model) {
            return $model->update($data);
        }
        return false;
    }
}