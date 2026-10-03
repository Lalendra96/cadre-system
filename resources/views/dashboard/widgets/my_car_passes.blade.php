<div class="status-pills">
    <div class="status-pill">
        <strong>{{ number_format($myCarPassStats['draft']) }}</strong>
        <span>Drafts</span>
    </div>
    <div class="status-pill">
        <strong>{{ number_format($myCarPassStats['pending']) }}</strong>
        <span>Pending Approval</span>
    </div>
    <div class="status-pill">
        <strong>{{ number_format($myCarPassStats['issued']) }}</strong>
        <span>Issued</span>
    </div>
</div>
