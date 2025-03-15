<!DOCTYPE html>
<html>
<head>
    <title>Chọn vai trò</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Chọn vai trò của bạn</h2>
        <form method="POST" action="{{ route('handle-role-selection') }}">
            @csrf
            <div class="mb-3">
                <label for="role" class="form-label">Vai trò:</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="">Chọn vai trò</option>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Xác nhận</button>
        </form>
    </div>
</body>
</html>