<?php

namespace App\Presenters;

use DateTime;

/**
 * Responsible for formatting Profile data for the View.
 */
class ProfilePresenter
{
    /**
     * Transforms raw profile data into a view-ready array.
     *
     * @param array $data The array returned from ProfileService::getProfileData
     * @return array The ViewModel with enriched presentation data
     */
    public function present(array $data): array
    {
        if (isset($data['profile']['created_at'])) {
            $data['profile']['formatted_created_at'] = (new DateTime($data['profile']['created_at']))->format('M Y');
        } else {
            $data['profile']['formatted_created_at'] = 'Unknown';
        }

        return $data;
    }
}
