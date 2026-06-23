<table class="table table-bordered">
    <thead>
        <tr style="text-align:center;">
            <th style="background-color:#08b325d3; color:white;">#</th>
            <th style="background-color:#08b325d3; color:white;">Location Name</th>
            <th style="background-color:#08b325d3; color:white;">Location Type</th>
            <th style="background-color:#08b325d3; color:white;">Location Address</th>
            <th style="background-color:#08b325d3; color:white;">Actions</th>
        </tr>
    </thead>

    <tbody style="text-align:center;">
        @foreach ($data as $item)
            <tr>
                <td>{{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->type }}</td>
                <td>{{ $item->address }}</td>
                <td>
                    <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                        <button class="btn btn-sm btn-primary edit_location_btn" data-id="{{ $item->id }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-sm btn-danger edit_location_delete" data-id="{{ $item->id }}">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
