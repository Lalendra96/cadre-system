<div class="status-pills">
    @foreach ($workflowBacklog as $row)
        <div class="status-pill">
            <strong>{{ number_format($row['value']) }}</strong>
            <span>{{ $row['label'] }}</span>
        </div>
    @endforeach
</div>
