<div style="overflow-x: auto;">
    <table class="mini-table">
        <thead>
            <tr>
                <th>Position</th>
                <th>Approved</th>
                <th>Recorded</th>
                <th>Gap</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vacancyRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ number_format($row['approved']) }}</td>
                    <td>{{ number_format($row['actual']) }}</td>
                    <td><strong>{{ number_format($row['gap']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
