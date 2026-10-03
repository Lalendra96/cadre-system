<div class="status-pills">
    <div class="status-pill">
        <strong>{{ number_format($carPassStats['pending']) }}</strong>
        <span>Pending Approval</span>
    </div>
    <div class="status-pill">
        <strong>{{ number_format($carPassStats['approved']) }}</strong>
        <span>Approved / Awaiting Issue</span>
    </div>
    <div class="status-pill">
        <strong>{{ number_format($carPassStats['issued']) }}</strong>
        <span>Issued</span>
    </div>
    <div class="status-pill">
        <strong>{{ number_format($carPassStats['expiring_30d']) }}</strong>
        <span>Expiring in 30 Days</span>
    </div>
</div>
