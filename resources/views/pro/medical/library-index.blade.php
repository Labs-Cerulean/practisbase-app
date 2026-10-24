@extends('layouts.app')

@section('page_title', 'Library')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.35rem; color: var(--primary-navy); margin: 0 0 0.25rem;">Library</h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Reference files while you are with a patient. Stored in the vault.</p>
        </div>
        <a href="/pro/medical/patients" style="color: var(--text-muted); font-weight: 600; text-decoration: none;">Patients</a>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 0.85rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; color: #991b1b; padding: 0.85rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if(empty($clinicDeskReady))
        <div style="background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; padding: 0.85rem 1rem; border-radius: var(--radius-md);">
            The library tables are not on this database yet.
        </div>
    @else
        <form action="/pro/medical/library" method="POST" enctype="multipart/form-data" style="background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.1rem 1.2rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem; display: grid; gap: 0.75rem;">
            @csrf
            <div>
                <label for="library-title" style="display: block; font-weight: 600; margin-bottom: 0.35rem;">Title</label>
                <input id="library-title" type="text" name="title" value="{{ old('title') }}" required maxlength="160" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: var(--radius-md);">
            </div>
            <div>
                <label for="library-file" style="display: block; font-weight: 600; margin-bottom: 0.35rem;">File</label>
                <input id="library-file" type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,application/pdf,image/jpeg,image/png,image/webp">
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">PDF, Word, or image. Max 15 MB.</div>
            </div>
            <button type="submit" style="justify-self: start; background: var(--primary-cerulean); color: white; border: none; padding: 0.7rem 1rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Add to library</button>
        </form>

        @if($documents->isEmpty())
            <p style="color: var(--text-muted);">No reference files yet.</p>
        @else
            <div style="display: grid; gap: 0.65rem;">
                @foreach($documents as $document)
                    <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: center; background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 0.85rem 1rem; box-shadow: var(--shadow-sm);">
                        <div style="font-weight: 700; color: var(--primary-navy);">{{ $document['title'] }}</div>
                        <div style="display: flex; gap: 0.45rem; align-items: center;">
                            <a href="/pro/medical/library/{{ $document['id'] }}/download" target="_blank" rel="noopener" style="color: var(--primary-cerulean); font-weight: 700; text-decoration: none;">Open</a>
                            <form action="/pro/medical/library/{{ $document['id'] }}" method="POST" style="margin: 0;" onsubmit="return confirm('Remove this file from the library?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: white; border: 1px solid #fecaca; color: #b91c1c; padding: 0.4rem 0.7rem; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
@endsection
