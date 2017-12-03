<?php

namespace App;

use App\Reply;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $guarded = [];
    protected $primaryKey = 'thread_id';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('replyCount', function($builder) {
            $builder->withCount('replies');
        });
    }

	public function path()
	{
        return "/threads/{$this->channel->slug}/{$this->thread_id}";
	}

    /**
     * A thread have many replies
     * @return \Illuminate\Database\Eloquent\Relations\
     */
	public function replies()
	{
		return $this->hasMany(Reply::class, 'thread_id', 'thread_id');
	}

    /**
     * A thread belongs to a creator
     * @return \Illuminate\Database\Eloquent\Relations\
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * A thread belongs to a channel
     * @return \Illuminate\Database\Eloquent\Relations\
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'channel_id');
    }

    /**
     * Add a reply to a thread
     * @param $reply
     */
    public function addReply($reply)
    {
        $this->replies()->create($reply);
    }

    /**
     * Filter threads according to ThreadsFilters
     * @param  $query
     * @param  \App\Filters\ThreadFilters $filters
     * @return \Illuminate\Http\Response
     */
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }
}
