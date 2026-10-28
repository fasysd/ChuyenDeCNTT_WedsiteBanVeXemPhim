@extends('layouts.admin')

@section('title', $title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1 text-light fw-bold">{{ $title }}</h3>
        <p class="text-muted mb-0">Nhập thông tin chi tiết của bộ phim.</p>
    </div>
    <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-light">
        &lt; Quay lại danh sách
    </a>
</div>

<div class="card p-4 border-secondary shadow-sm">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tên phim <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $movie->title) }}" placeholder="Ví dụ: Avengers: Secret Wars" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Thể loại</label>
                <input type="text" name="genre" class="form-control" value="{{ old('genre', $movie->genre) }}" placeholder="Ví dụ: Hành động, Viễn tưởng">
            </div>

            <div class="col-md-3">
                <label class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
                <input type="number" name="duration" class="form-control" min="1" value="{{ old('duration', $movie->duration) }}" placeholder="Ví dụ: 120" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Ngày khởi chiếu</label>
                <input type="date" name="release_date" class="form-control" value="{{ old('release_date', $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('Y-m-d') : '') }}">
            </div>

            <div class="col-md-8">
                <label class="form-label">Đường dẫn ảnh poster</label>
                <input type="text" name="image_path" class="form-control" value="{{ old('image_path', $movie->image_path) }}" placeholder="images/movieavatar.webp hoặc URL ảnh online">
            </div>

            <div class="col-12">
                <label class="form-label">Mô tả nội dung phim</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Tóm tắt cốt truyện phim...">{{ old('description', $movie->description) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
            <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary px-4">
                {{ $isEdit ? 'Lưu thay đổi' : 'Thêm phim' }}
            </button>
        </div>
    </form>
</div>
@endsection
