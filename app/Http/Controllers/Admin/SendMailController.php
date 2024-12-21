<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use App\Models\Secondaryuser;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;

class SendMailController extends Controller
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function showForm()
    {
        $templates = MailTemplate::where('is_active', true)->get();

        return view('admin.send_mail_form', compact('templates'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:mail_templates,id',
            'email' => 'required|email',
            'name' => 'nullable|string',
            'variables' => 'nullable|json'
        ]);

        $template = MailTemplate::findOrFail($request->template_id);
        $variables = $request->variables ? json_decode($request->variables, true) : [];

        try {
            $this->mailService->queueFromTemplate(
                $template->name,
                $request->email,
                $variables,
                $request->name
            );

            return back()->with('success', 'Письмо добавлено в очередь на отправку');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function getTemplate($id)
    {
        $template = MailTemplate::findOrFail($id);
        return response()->json([
            'variables' => $template->variables ?? [],
            'subject' => $template->subject,
            'html_preview' => $template->html_body
        ]);
    }
}
