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
            $presentedTopics = [];
            foreach ($data['topics'] as $topic) {
                // Convert readonly entity to mutable stdClass
                $obj = $this->toMutable($topic);
                
                // Format last_reply_at
                $obj->formatted_last_reply_at = (new DateTime($obj->last_reply_at))->format('M d, H:i');
                // Ensure other fields are present or defaulted if null
                $obj->author_name = $obj->author_name ?? 'N/A';
                $obj->last_reply_user_name = $obj->last_reply_user_name ?? 'N/A';
                
                $presentedTopics[] = $obj;
            }
            $data['topics'] = $presentedTopics;
        }

        // Nest permissions for consistent view logic
        $data['permissions'] = (object)[
            'can_manage_forum' => $data['canManageForum'] ?? false,
            'can_create_topic' => true // Assumed if you can see the forum
        ];
        unset($data['canManageForum']);

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
            $obj = $this->toMutable($data['topic']);
            $obj->formatted_created_at = (new DateTime($obj->created_at))->format('M d, Y');
            $obj->author_name = $obj->author_name ?? 'N/A';
            $data['topic'] = $obj;
        }

        // Format Posts Dates
        if (!empty($data['posts'])) {
            $presentedPosts = [];
            foreach ($data['posts'] as $post) {
                $obj = $this->toMutable($post);
                $obj->formatted_created_at = (new DateTime($obj->created_at))->format('M d, Y \a\t H:i');
                $obj->author_name = $obj->author_name ?? 'N/A';
                $presentedPosts[] = $obj;
            }
            $data['posts'] = $presentedPosts;
        }

        // Nest permissions for consistent view logic
        $data['permissions'] = (object)[
            'can_manage_forum' => $data['canManageForum'] ?? false
        ];
        unset($data['canManageForum']);

        return $data;
    }

    /**
     * Helper to convert a readonly entity into a mutable stdClass object.
     * This preserves the property access syntax (->) for views.
     */
    private function toMutable(object $entity): object
    {
        // get_object_vars gets accessible properties. 
        // Since Entity props are public readonly, this works perfectly.
        return (object) get_object_vars($entity);
    }
}
