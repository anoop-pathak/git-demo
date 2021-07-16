<?php

namespace App\Repositories;

use App\Models\EmailAutoRespondTemplate;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class AutoRespondTemplateRepository extends ScopedRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EmailAutoRespondTemplate $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    /**
     * @ save auto respond template
     */
    public function createOrUpdateTemplate($subject, $content, $active = false)
    {
        $template = EmailAutoRespondTemplate::firstOrNew(['user_id' => \Auth::id()]);

        $template->subject = $subject;
        $template->content = $content;
        $template->active = $active;

        $template->save();


        return $template;
    }

    public function getActiveTemplate()
    {
        $template = EmailAutoRespondTemplate::whereUserId(\Auth::id())
            ->whereActive(true)
            ->first();

        return $template;
    }

    public function getTemplate()
    {
        $template = EmailAutoRespondTemplate::whereUserId(\Auth::id())->first();

        return $template;
    }
}
