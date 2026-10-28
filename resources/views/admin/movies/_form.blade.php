@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @if($movie->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tên phim <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $movie->title) }}" placeholder="Nhập tên phim" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Thể loại</label>
            <input type="text" name="genre" class="form-control" value="{{ old('genre', $movie->genre) }}" placeholder="Ví dụ: Hành động">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ngày phát hành</label>
            <input type="date" name="release_date" class="form-control" value="{{ old('release_date', $movie->release_date?->format('Y-m-d')) }}">
        </div>
        <div class="col-md-8">
            <label class="form-label">Ảnh bìa (URL hoặc đường dẫn)</label>
            <input type="text" name="image_path" class="form-control" value="{{ old('image_path', $movie->image_path) }}" placeholder="images/movieavatar.webp">
        </div>
        <div class="col-md-4">
            <label class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
            <input type="number" name="duration" class="form-control" min="1" value="{{ old('duration', $movie->duration) }}" placeholder="120" required>
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả nội dung</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Tóm tắt nội dung phim...">{{ old('description', $movie->description) }}</textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
        @if($useModalCancel ?? false)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        @else
            <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
        @endif
        <button type="submit" class="btn btn-primary px-4">
            {{ $isEdit ? 'Lưu thay đổi' : 'Thêm phim' }}
        </button>
    </div>
</form>
