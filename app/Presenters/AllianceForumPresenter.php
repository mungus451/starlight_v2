<?php

namespace App\Presenters;

use DateTime;

/**
 * Responsible for formatting Alliance Forum data for the View.
 */
class AllianceForumPresenter
{
    /**
     * Transforms forum index data (list of topics).
     *
     * @param array $data Data from AllianceForumService::getForumIndexData
     * @return array Enriched data
     */
    public function presentIndex(array $data): array
    {
        if (!empty($data['topics'])) {
            foreach ($data['topics'] as $topic) {
                // Format last_reply_at
                $topic->formatted_last_reply_at = (new DateTime($topic->last_reply_at))->format('M d, H:i');
                // Ensure other fields are present or defaulted if null
                $topic->author_name = $topic->author_name ?? 'N/A';
                $topic->last_reply_user_name = $topic->last_reply_user_name ?? 'N/A';
            }
        }
        return $data;
    }

    /**
     * Transforms topic details data (topic + posts).
     *
     * @param array $data Data from AllianceForumService::getTopicDetails
     * @return array Enriched data
     */
    public function presentTopic(array $data): array
    {
        // Format Topic Date
        if (isset($data['topic'])) {
            $data['topic']->formatted_created_at = (new DateTime($data['topic']->created_at))->format('M d, Y');
            $data['topic']->author_name = $data['topic']->author_name ?? 'N/A';
        }

        // Format Posts Dates
        if (!empty($data['posts'])) {
            foreach ($data['posts'] as $post) {
                $post->formatted_created_at = (new DateTime($post->created_at))->format('M d, Y \a\t H:i');
                $post->author_name = $post->author_name ?? 'N/A';
            }
        }

        return $data;
    }
}
