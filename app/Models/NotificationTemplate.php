<?php

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasStatus;

    protected $fillable = [
        'code',
        'name',
        'channel',
        'title_template',
        'content_template',
        'variables',
        'status',
        'remark',
    ];

    protected $casts = [
        'variables' => 'array',
        'status' => 'integer',
    ];

    /**
     * Render title with variables.
     */
    public function renderTitle(array $data): string
    {
        return $this->renderTemplate($this->title_template, $data);
    }

    /**
     * Render content with variables.
     */
    public function renderContent(array $data): string
    {
        return $this->renderTemplate($this->content_template, $data);
    }

    /**
     * Render template with variable replacement.
     */
    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
                $template = str_replace('{{ ' . $key . ' }}', (string) $value, $template);
            }
        }

        return $template;
    }

    /**
     * Get template by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->where('status', self::STATUS_ENABLED)->first();
    }

    /**
     * Get templates by channel.
     */
    public static function getByChannel(string $channel)
    {
        return static::where('channel', $channel)->where('status', self::STATUS_ENABLED)->get();
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
