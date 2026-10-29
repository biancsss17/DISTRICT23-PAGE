<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\Officer;
use App\Models\User;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_public_pages_and_admin_access(): void
    {
        foreach (['/', '/news', '/achievements', '/officers', '/admin/login'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/admin/editor/records/news')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/editor/records/news')->assertForbidden();
        $this->actingAs($this->administrator())->get('/admin')->assertOk()->assertSee('Records')->assertSee('Manage Officers');
        $this->get('/admin')->assertDontSee('Change my password');
        $this->put('/admin/editor/password')->assertNotFound();
        foreach (['/admin/content/news', '/admin/settings/home', '/admin/officers', '/admin/overview'] as $oldPath) {
            $this->get($oldPath)->assertNotFound();
        }
    }

    public function test_news_and_achievement_records_are_managed_in_visual_editor(): void
    {
        $this->actingAs($this->administrator());
        foreach (['news', 'achievements'] as $type) {
            $slug = 'editor-'.$type;
            $path = '/admin/editor/records/'.$type;
            $this->postJson($path, [
                'title' => 'Editor '.$type, 'slug' => $slug, 'content' => 'Approved story',
                'status' => 'draft', 'sort_order' => 0,
            ])->assertOk()->assertJsonPath('record.slug', $slug);
            $record = Content::where('slug', $slug)->firstOrFail();
            $this->getJson($path)->assertOk()->assertJsonFragment(['title' => 'Editor '.$type]);
            $this->get('/'.$type.'/'.$slug)->assertNotFound();
            $this->get($path.'/'.$record->id.'/preview')->assertOk()->assertSee('Private preview');
            $this->postJson($path.'/'.$record->id, [
                'title' => 'Published '.$type, 'slug' => $slug, 'content' => 'Approved story',
                'status' => 'published', 'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'sort_order' => 0,
            ])->assertOk();
            $this->get('/'.$type.'/'.$slug)->assertOk()->assertSee('Published '.$type);
            $this->deleteJson($path.'/'.$record->id)->assertOk();
            $this->assertDatabaseMissing('contents', ['id' => $record->id]);
        }
    }

    public function test_officers_save_and_delete_without_legacy_pages(): void
    {
        $this->actingAs($this->administrator());
        $path = '/admin/editor/records/officers';
        $response = $this->postJson($path, [
            'name' => 'Editor officer', 'position' => 'Coordinator', 'term' => '2026-2028',
            'status' => 'published', 'sort_order' => 1,
        ])->assertOk();
        $id = $response->json('record.id');
        $this->get('/officers')->assertSee('Editor officer');
        $this->postJson($path.'/'.$id, [
            'name' => 'Updated officer', 'position' => 'Coordinator', 'term' => '2027-2029',
            'status' => 'published', 'sort_order' => 1,
        ])->assertOk();
        $this->assertSame('2027-2029', Officer::findOrFail($id)->term);
        $this->deleteJson($path.'/'.$id)->assertOk();
        $this->assertDatabaseMissing('officers', ['id' => $id]);
    }

    public function test_validation_and_media_limits_remain_enforced(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'public']);
        $this->actingAs($this->administrator());
        $path = '/admin/editor/records/news';
        $this->postJson($path, [])->assertUnprocessable();
        $this->postJson($path, [
            'title' => 'Bad slug', 'slug' => '<script>', 'content' => 'Body',
            'status' => 'published', 'sort_order' => 0,
        ])->assertUnprocessable();
        $file = UploadedFile::fake()->create('large.mp4', 51201, 'video/mp4');
        $this->postJson($path, [
            'title' => 'Large file', 'slug' => 'large-file', 'content' => 'Body',
            'status' => 'draft', 'sort_order' => 0, 'gallery_uploads' => [$file],
        ])->assertUnprocessable();

        $this->postJson($path, [
            'title' => 'Large photo', 'slug' => 'large-photo', 'content' => 'Body',
            'status' => 'draft', 'sort_order' => 0,
            'gallery_uploads' => [UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg')],
        ])->assertUnprocessable()->assertJsonValidationErrors('gallery_uploads.0')
            ->assertSee('Photos must be 10 MB or smaller.');

        $this->postJson('/admin/editor/records/officers', [
            'name' => 'Large portrait', 'position' => 'Coordinator',
            'status' => 'draft', 'sort_order' => 0,
            'photo_upload' => UploadedFile::fake()->create('portrait.jpg', 10241, 'image/jpeg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('photo_upload');
    }

    public function test_media_deletion_clears_shared_references(): void
    {
        Storage::fake('public');
        $this->actingAs($this->administrator());
        $media = Media::create(['path' => 'cms/editor-delete.png', 'name' => 'editor-delete.png']);
        Storage::disk('public')->put($media->path, 'test bytes');
        $record = Content::where('type', 'news')->firstOrFail();
        $record->cover_id = $media->id;
        $record->save();
        HeroSlide::create(['media_id' => $media->id, 'sort_order' => 50]);
        $this->deleteJson('/admin/editor/media/'.$media->id)->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertNull($record->fresh()->cover_id);
        $this->assertDatabaseMissing('hero_slides', ['media_id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }
}
