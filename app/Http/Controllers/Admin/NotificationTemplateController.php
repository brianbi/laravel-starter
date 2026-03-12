<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    use ApiResponse;

    /**
     * List notification templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query();

        if ($channel = $request->input('channel')) {
            $query->byChannel($channel);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $templates = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->success($templates);
    }

    /**
     * Create notification template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:notification_templates'],
            'name' => ['required', 'string', 'max:100'],
            'channel' => ['required', 'string', 'max:20'],
            'title_template' => ['required', 'string', 'max:200'],
            'content_template' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'status' => ['nullable', 'in:0,1'],
            'remark' => ['nullable', 'string'],
        ]);

        $template = NotificationTemplate::create($validated);

        return $this->created($template, '模板创建成功');
    }

    /**
     * Get template detail.
     */
    public function show(int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        return $this->success($template);
    }

    /**
     * Update notification template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:50', "unique:notification_templates,code,{$id}"],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'channel' => ['sometimes', 'required', 'string', 'max:20'],
            'title_template' => ['sometimes', 'required', 'string', 'max:200'],
            'content_template' => ['sometimes', 'required', 'string'],
            'variables' => ['nullable', 'array'],
            'status' => ['nullable', 'in:0,1'],
            'remark' => ['nullable', 'string'],
        ]);

        $template->update($validated);

        return $this->success($template, '模板更新成功');
    }

    /**
     * Delete notification template.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return $this->noContent('模板删除成功');
    }

    /**
     * Preview template rendering.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $request->validate([
            'data' => ['nullable', 'array'],
        ]);

        $data = $request->input('data', []);

        return $this->success([
            'title' => $template->renderTitle($data),
            'content' => $template->renderContent($data),
        ]);
    }
}
