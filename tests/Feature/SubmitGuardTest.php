<?php

namespace Tests\Feature;

use App\Http\Middleware\SignalDownloadFinished;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SubmitGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->app->forgetInstance('encrypter');
    }

    public function test_the_signed_in_shell_loads_the_submit_guard(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $script = file_get_contents(public_path('js/submit-guard.js'));

        $this->assertIsString($layout);
        $this->assertIsString($script);
        $this->assertStringContainsString('/js/submit-guard.js', $layout);
        $this->assertStringContainsString("textContent = 'Working…'", $script);
        $this->assertStringContainsString('data-pb-submitting', $script);
    }

    public function test_a_second_submit_is_ignored_until_the_request_finishes(): void
    {
        $node = trim((string) shell_exec('command -v node'));
        $this->assertNotSame('', $node, 'node is required to run the submit guard');

        $process = new Process([$node, base_path('tests/js/submit-guard.test.cjs')]);
        $process->setTimeout(20);
        $process->mustRun();

        $this->assertStringContainsString('submit guard ok', $process->getOutput());
    }

    public function test_a_file_download_tells_the_page_the_request_finished(): void
    {
        $token = 'abc123abc123abc123';

        Route::post('/testing/download-cookie', function () {
            return response('PK', 200, [
                'Content-Disposition' => 'attachment; filename="pack.zip"',
            ]);
        })->middleware('web');

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->withUnencryptedCookie(SignalDownloadFinished::WAIT, $token)
            ->post('/testing/download-cookie');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($item) => $item->getName() === SignalDownloadFinished::DONE);

        $this->assertNotNull($cookie);
        $this->assertSame($token, $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());
    }

    public function test_an_ordinary_save_does_not_set_the_download_cookie(): void
    {
        Route::post('/testing/save-cookie', function () {
            return redirect('/company/expenses');
        })->middleware('web');

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->withUnencryptedCookie(SignalDownloadFinished::WAIT, 'abc123abc123abc123')
            ->post('/testing/save-cookie');

        $names = collect($response->headers->getCookies())->map->getName()->all();

        $this->assertNotContains(SignalDownloadFinished::DONE, $names);
    }

    public function test_a_link_download_does_not_release_a_busy_form(): void
    {
        Route::get('/testing/get-download', function () {
            return response('PK', 200, [
                'Content-Disposition' => 'attachment; filename="pack.zip"',
            ]);
        })->middleware('web');

        $response = $this->withUnencryptedCookie(SignalDownloadFinished::WAIT, 'abc123abc123abc123')
            ->get('/testing/get-download');

        $names = collect($response->headers->getCookies())->map->getName()->all();

        $this->assertNotContains(SignalDownloadFinished::DONE, $names);
    }
}
