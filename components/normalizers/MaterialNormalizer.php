<?php

namespace mikemadisonweb\elasticsearch\components\normalizers;

class MaterialNormalizer implements NormalizerInterface
{
    public $availableProducts;

    /**
     * Id пользователя для просмотра которого собирается материал
     */
    protected $user;
    protected $molecules;
    protected $personalSearches;
    protected $tags;
    protected $colors;
    protected $spreadsheets;

    /**
     * @param array $materials
     * @return array
     */
    public function normalize(array $materials) : array
    {
        if (empty($materials)) {
            return [];
        }
        $normalized = [];
        foreach ($materials as $material) {
            $normalized[] = $this->normalizeMaterial($material);
        }

        return $normalized;
    }

    /**
     * @param array $material
     * @return array
     */
    public function normalizeMaterial($material) : array
    {
        return [
            'id' => $material['id'],
            'headline' => $material['headline'],
            'text' => !empty($material['text']) ? strip_tags($material['text']) : '',
            'location' => $material['location'],
            'slug' => $material['slug'],
            'keywords' => $material['keywords'],
            'news_date' => !empty($material['news_date']) ? strtotime($material['news_date']) : '',
            'products' => !empty($material['products']) ? explode(',', $material['products']) : [],
            'rubric_id' => $material['rubric_id'],
            'genre_id' => $material['rubric_id'],
            'priority' => $material['priority'],
        ];
    }
}
