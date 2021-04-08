<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/4/8/008
 * Time: 15:08
 */

namespace App\Models\Traits;


use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait ActiveUsersTrait
{
    protected $users = [];

    //配置信息
    protected $topic_weight = 4;
    protected $reply_weight = 1;
    protected $pass_days = 7; //多少天内发表过内容
    protected $user_number = 7; //取出来多少用户

    // 缓存相关配置
    protected $cache_key = 'larabbs_active_users';
    protected $cache_expire_in_seconds = 65 * 60;

    public function getActiveUsers()
    {
        return Cache::remember($this->cache_key, $this->cache_expire_in_seconds, function () {
            return $this->calculateActiveUsers();
        });
    }

    public function calculateAndCacheActiveUsers()
    {
        // 取得活跃用户列表
        $active_users = $this->calculateActiveUsers();
        // 并加以缓存
        $this->cacheActiveUsers($active_users);
    }

    private function cacheActiveUsers($active_users)
    {
        // 将数据放入缓存中
        Cache::put($this->cache_key, $active_users, $this->cache_expire_in_seconds);
    }

    private function calculateActiveUsers() {
        $this->calculateTopicScore();
        $this->calculateReplyScore();
        arsort($this->users);
        $this->users = array_slice($this->users, 0, $this->user_number, true);

        // 新建一个空集合
        $active_users = collect();
        $users = User::query()->whereIn('id', array_keys($this->users))->get()->keyBy('id');
        foreach ($this->users as $key => $value) {
            $user = $users->get($key);
            if ($user) {
                $active_users->push($user);
            }
        }
        // 返回数据
        return $active_users;
    }

    private function calculateTopicScore()
    {
        // 从话题数据表里取出限定时间范围（$pass_days）内，有发表过话题的用户
        // 并且同时取出用户此段时间内发布话题的数量
        $topic_users = Topic::query()->select(DB::raw('user_id, count(*) as topic_count'))
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_days))
            ->groupBy('user_id')
            ->get();
        foreach ($topic_users as $value) {
            $this->users[$value['user_id']] = $value['topic_count'] * $this->topic_weight;
        }
    }

    private function calculateReplyScore()
    {
        // 从话题数据表里取出限定时间范围（$pass_days）内，有发表过回复的用户
        // 并且同时取出用户此段时间内发布回复的数量
        $reply_users = Reply::query()->select(DB::raw('user_id, count(*) as reply_count'))
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_days))
            ->groupBy('user_id')
            ->get();
        foreach ($reply_users as $value) {
            if (array_key_exists($value['user_id'], $this->users)) {
                $this->users[$value['user_id']] += $value['reply_count'] * $this->reply_weight;
            } else {
                $this->users[$value['user_id']] = $value['reply_count'] * $this->reply_weight;
            }
        }
    }
}