<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/4/8/008
 * Time: 17:46
 */

namespace App\Models\Traits;


use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

trait LastActivedAtHelper
{
    protected $hash_prefix = 'larabbs_last_actived_at_';
    protected $field_prefix = 'user_';

    public function recordLastActivedAt()
    {
        $date = Carbon::now()->toDateString();
        $hash = $this->getHashFromDateString($date);
        $field = $this->getHashField();

        // 当前时间，如：2017-10-21 08:35:15
        $now = Carbon::now()->toDateTimeString();

        // 数据写入 Redis ，字段已存在会被更新
        Redis::hSet($hash, $field, $now);
    }

    public function syncUserActivedAt()
    {
        $yestoday = Carbon::yesterday()->toDateString();
        $hash = $this->getHashFromDateString($yestoday);
        $yestodayUserSet = Redis::hGetAll($hash);
        if (!empty($yestodayUserSet)) {
            $userSetKeys = array_keys($yestodayUserSet);
            $userIds = array_map(function($value) {
                return str_replace($this->field_prefix, '', $value);
            }, $userSetKeys);
            $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($yestodayUserSet as $field => $item) {
                $userId = str_replace($this->field_prefix, '', $field);
                $user = $users->get($userId);
                if ($user) {
                    $user->update([
                        'last_actived_at' => $item
                    ]);
                }
            }
        }
        Redis::del($this->hash_prefix . $yestoday);
    }

    protected function getHashFromDateString($dateString) {
        // Redis 哈希表的命名，如：larabbs_last_actived_at_2017-10-21
        return $this->hash_prefix . $dateString;
    }

    protected function getHashField() {
        // 字段名称，如：user_1
        return $this->field_prefix . $this->id;
    }
}