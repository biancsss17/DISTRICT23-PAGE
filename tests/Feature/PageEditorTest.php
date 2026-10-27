<?php

namespace Tests\Feature;

use App\Models\{Media,User,SiteSetting};
use App\Services\PageEditor;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    private function administrator(): User
    {
        $user=User::factory()->create();
        $user->is_admin=true;
        $user->save();
        return $user;
    }

    public function test_editor_is_admin_only_and_uses_real_homepage(): void
    {
        $this->get('/admin/editor')->assertRedirect('/admin/login');
        $this->get('/admin/editor/preview?editor-preview=1')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/editor')->assertForbidden();
        $this->actingAs($this->administrator())->get('/admin')->assertOk()->assertSee('Your editor.')->assertSee('Save draft');
        $this->get('/admin/editor/preview?editor-preview=1')->assertOk()->assertSee('hero-backdrops')->assertSee('page-editor-style');
    }

    public function test_draft_is_private_publish_and_restore(): void
    {
        $original=PageEditor::current();
        $data=$original;
        $data['revision']='';
        $data['settings']['hero_heading']='A private draft headline';
        $data['styles']=['hero'=>['font'=>'Georgia','size'=>72,'color'=>'#123456'],'background'=>['mode'=>'gradient','start'=>'#ffffff','end'=>'#ddeeff','angle'=>120]];
        $this->actingAs($this->administrator());
        $response=$this->putJson('/admin/editor/draft',$data)->assertOk();
        $this->get('/')->assertDontSee('A private draft headline');
        $this->get('/admin/editor/preview?editor-preview=1')->assertOk()->assertSee('A private draft headline')->assertSee('Georgia, serif')->assertSee('linear-gradient(120deg');
        $this->postJson('/admin/editor/publish',['revision'=>$response->json('revision')])->assertOk();
        $this->get('/')->assertSee('A private draft headline')->assertSee('Georgia, serif');
        $this->postJson('/admin/editor/restore')->assertOk();
        $this->assertEquals($original['settings'],PageEditor::current()['settings']);
        $this->get('/')->assertDontSee('A private draft headline');
    }

    public function test_validation_and_conflicts_protect_settings(): void
    {
        $this->actingAs($this->administrator());
        $data=PageEditor::current();$data['revision']='';
        $bad=$data;$bad['styles']=['hero'=>['font'=>'</style><script>alert(1)</script>']];
        $this->putJson('/admin/editor/draft',$bad)->assertUnprocessable();
        $bad=$data;$bad['settings']['primary_url']='javascript:alert(1)';
        $this->putJson('/admin/editor/draft',$bad)->assertUnprocessable();
        $response=$this->putJson('/admin/editor/draft',$data)->assertOk();
        $this->putJson('/admin/editor/draft',$data)->assertConflict();
        SiteSetting::updateOrCreate(['key'=>'hero_heading'],['value'=>'Edited in another form']);
        $this->postJson('/admin/editor/publish',['revision'=>$response->json('revision')])->assertConflict();
    }

    public function test_sections_and_reset_preserve_public_content(): void
    {
        $this->actingAs($this->administrator());
        $data=PageEditor::current();$data['revision']='';
        $data['sections'][0]['title']='Draft section title';
        $data['sections'][0]['visible']=true;
        $this->putJson('/admin/editor/draft',$data)->assertOk();
        $this->get('/admin/editor')->assertOk()->assertSee('Draft section title');
        $this->get('/admin/editor/preview?editor-preview=1')->assertSee('Draft section title');
        $this->get('/')->assertDontSee('Draft section title');
        $this->deleteJson('/admin/editor/draft')->assertOk();
        $this->assertNull(PageEditor::read('visual_draft'));
    }

    public function test_media_upload_does_not_publish_a_slide(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        config(['media.disk'=>'public']);
        $this->actingAs($this->administrator());
        $before=PageEditor::current()['slides'];
        $image=\Illuminate\Http\UploadedFile::fake()->createWithContent('cover.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $response=$this->postJson('/admin/editor/media',['file'=>$image])->assertOk();
        $this->assertNotEmpty($response->json('id'));
        $this->assertEquals($before,PageEditor::current()['slides']);
        $this->postJson('/admin/editor/media',['file'=>\Illuminate\Http\UploadedFile::fake()->create('bad.txt',1,'text/plain')])->assertUnprocessable();
        $this->postJson('/admin/editor/media',['file'=>\Illuminate\Http\UploadedFile::fake()->create('huge.mp4',51201,'video/mp4')])->assertUnprocessable();
    }

    public function test_media_upload_uses_10_mb_for_photos_and_50_mb_for_videos(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        config(['media.disk' => 'public']);
        $this->actingAs($this->administrator());

        $this->postJson('/admin/editor/media', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('within-limit.jpg', 10240, 'image/jpeg'),
        ])->assertOk();

        $this->postJson('/admin/editor/media', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file')
            ->assertSee('Photos must be 10 MB or smaller.');

        $this->postJson('/admin/editor/media', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('video.mp4', 25601, 'video/mp4'),
        ])->assertOk();

        $this->postJson('/admin/editor/media', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('large.mp4', 51201, 'video/mp4'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file')
            ->assertSee('Videos must be 50 MB or smaller.');
    }

    public function test_neon_upload_is_served_and_deleted_from_its_recorded_disk(): void
    {
        \Illuminate\Support\Facades\Storage::fake('neon');
        config(['media.disk' => 'neon']);
        $this->actingAs($this->administrator());

        $image = \Illuminate\Http\UploadedFile::fake()->createWithContent('neon-cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $response = $this->postJson('/admin/editor/media', ['file' => $image])->assertOk();
        $media = Media::findOrFail($response->json('id'));

        $this->assertSame('neon', $media->storage_disk);
        \Illuminate\Support\Facades\Storage::disk('neon')->assertExists($media->path);
        $this->get('/media/'.$media->id)->assertOk();

        $this->deleteJson('/admin/editor/media/'.$media->id)->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        \Illuminate\Support\Facades\Storage::disk('neon')->assertMissing($media->path);
    }

    public function test_neon_connection_check_cleans_up_its_probe(): void
    {
        \Illuminate\Support\Facades\Storage::fake('neon');
        config([
            'filesystems.disks.neon.key' => 'test-key',
            'filesystems.disks.neon.secret' => 'test-secret',
            'filesystems.disks.neon.region' => 'us-east-2',
            'filesystems.disks.neon.bucket' => 'test-bucket',
            'filesystems.disks.neon.endpoint' => 'https://example.invalid',
        ]);

        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('media:check-neon'));
        $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('neon')->allFiles('cms'));
    }

    public function test_css_is_bounded_and_does_not_accept_markup(): void
    {
        $css=PageEditor::css(['hero'=>['font'=>'</style>','color'=>'red;}</style>','size'=>9999]]);
        $this->assertStringNotContainsString('</style>',$css);
        $this->assertStringNotContainsString('9999',$css);
        $this->assertStringContainsString('120px',$css);
    }
}
