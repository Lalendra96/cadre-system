<style>
    .enterprise-head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px
    }

    .enterprise-head h1 {
        margin: 0;
        font-size: 28px;
        color: var(--md-on-surface)
    }

    .enterprise-head p {
        margin: 6px 0 0;
        color: var(--md-on-surface-variant);
        max-width: 820px
    }

    .enterprise-badge {
        padding: 7px 11px;
        border-radius: 999px;
        background: var(--md-primary-container);
        color: var(--md-on-primary-container);
        font-size: 12px;
        font-weight: 800
    }

    .enterprise-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 12px;
        margin: 16px 0
    }

    .enterprise-kpi,
    .enterprise-card {
        background: var(--md-surface-container-low);
        border: 1px solid var(--md-outline-variant);
        border-radius: 16px;
        padding: 16px;
        box-shadow: var(--md-elevation-1)
    }

    .enterprise-kpi strong {
        display: block;
        font-size: 26px;
        margin-top: 8px
    }

    .enterprise-kpi span {
        font-size: 12px;
        color: var(--md-on-surface-variant);
        font-weight: 700
    }

    .enterprise-card h3 {
        margin: 0 0 8px;
        font-size: 16px
    }

    .enterprise-card p {
        font-size: 13px;
        color: var(--md-on-surface-variant);
        min-height: 38px
    }

    .enterprise-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px
    }

    .enterprise-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 11px;
        border-radius: 10px;
        background: var(--md-primary-container);
        color: var(--md-on-primary-container);
        text-decoration: none;
        font-size: 12px;
        font-weight: 800
    }

    .enterprise-section {
        margin-top: 20px
    }

    .enterprise-section h2 {
        font-size: 19px;
        margin: 0 0 10px
    }

    .enterprise-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px
    }

    .enterprise-table th,
    .enterprise-table td {
        padding: 9px;
        border-bottom: 1px solid var(--md-outline-variant);
        text-align: left
    }

    .enterprise-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px
    }

    .enterprise-form input,
    .enterprise-form select,
    .enterprise-form textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 9px;
        border: 1px solid var(--md-outline-variant);
        border-radius: 9px;
        background: var(--md-surface);
        color: var(--md-on-surface)
    }

    .enterprise-form textarea {
        min-height: 78px
    }

    .enterprise-form .span2 {
        grid-column: span 2
    }

    .enterprise-note {
        padding: 12px;
        border-left: 4px solid var(--md-primary);
        background: var(--md-primary-container);
        border-radius: 10px;
        font-size: 12px;
        margin: 12px 0
    }

    .status-good {
        color: var(--md-success);
        font-weight: 800
    }

    .status-warn {
        color: var(--md-warning);
        font-weight: 800
    }

    .status-bad {
        color: var(--md-error);
        font-weight: 800
    }

    @media(max-width:700px) {
        .enterprise-head {
            display: block
        }

        .enterprise-form .span2 {
            grid-column: span 1
        }
    }
</style>
