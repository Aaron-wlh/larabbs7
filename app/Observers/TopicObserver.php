<?php

namespace App\Observers;

use App\Handlers\SlugTranslateHandler;
use App\Jobs\TranslateSlug;
use App\Models\Topic;

// creating, created, updating, updated, saving,
// saved,  deleting, deleted, restoring, restored

class TopicObserver
{
    public function saving(Topic $topic)
    {
        $topic->body = clean($topic->body, 'user_topic_body');
        $topic->excerpt = make_excerpt($topic->body);

    }


    public function saved(Topic $topic)
    {
        if (!app()->runningInConsole()) {
            //队列系统对于构造器里传入的 Eloquent 模型，将会只序列化 ID 字段
            if (! $topic->slug) {
                // 推送任务到队列
                dispatch(new TranslateSlug($topic));
            }
        }
    }

    public function deleted(Topic $topic)
    {
        \DB::table('replies')->where('topic_id', $topic->id)->delete();
    }
}