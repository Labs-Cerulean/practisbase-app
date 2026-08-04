{{-- Shared Engineer field / mobile polish styles --}}
<style>
    .eng-field-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.55rem;
        background: white;
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 0.85rem;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.25rem;
    }
    .eng-field-strip-label {
        grid-column: 1 / -1;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin: 0 0 0.15rem;
    }
    .eng-field-strip a {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 0.15rem;
        min-height: 3.4rem;
        padding: 0.65rem 0.75rem;
        border-radius: var(--radius-md);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--primary-navy);
        background: #f8fafc;
        border: 1px solid var(--border-light);
        -webkit-tap-highlight-color: transparent;
    }
    .eng-field-strip a span {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    .eng-field-strip a.eng-field-primary {
        background: var(--primary-cerulean);
        border-color: var(--primary-cerulean);
        color: white;
    }
    .eng-field-strip a.eng-field-primary span { color: rgba(255,255,255,0.85); }

    .eng-field-form input[type="text"],
    .eng-field-form input[type="date"],
    .eng-field-form input[type="tel"],
    .eng-field-form select,
    .eng-field-form textarea {
        font-size: 16px;
        min-height: 2.75rem;
    }
    .eng-field-form textarea { min-height: 5.5rem; }
    .eng-field-form .eng-touch-btn {
        min-height: 2.5rem;
        padding: 0.5rem 0.85rem;
        font-size: 0.88rem;
    }

    .eng-photo-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.55rem;
    }
    .eng-photo-actions button {
        min-height: 3.1rem;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }
    .eng-photo-camera {
        background: var(--primary-cerulean);
        color: white;
        border: none;
    }
    .eng-photo-library {
        background: white;
        color: var(--primary-navy);
        border: 1px solid var(--border-light);
    }
    .eng-photo-previews {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
        gap: 0.55rem;
        margin-top: 0.35rem;
    }
    .eng-photo-previews .eng-photo-thumb {
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        overflow: hidden;
        background: #f8fafc;
        aspect-ratio: 1;
        position: relative;
    }
    .eng-photo-previews img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .eng-photo-previews .eng-photo-remove {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        width: 1.6rem;
        height: 1.6rem;
        border: none;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.75);
        color: white;
        font-weight: 700;
        cursor: pointer;
        line-height: 1;
    }

    .eng-field-savebar {
        position: sticky;
        bottom: 0.75rem;
        z-index: 20;
        margin-top: 0.5rem;
        padding: 0.65rem;
        background: rgba(248, 250, 252, 0.94);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        backdrop-filter: blur(6px);
        display: flex;
        gap: 0.55rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .eng-field-savebar button[type="submit"] {
        background: var(--primary-cerulean);
        color: white;
        border: none;
        padding: 0.85rem 1.2rem;
        border-radius: var(--radius-md);
        font-weight: 700;
        cursor: pointer;
        min-height: 3rem;
        flex: 1 1 auto;
        font-size: 1rem;
    }
    .eng-field-savebar .eng-field-hint {
        font-size: 0.78rem;
        color: var(--text-muted);
        flex: 1 1 100%;
    }

    .eng-sticky-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    @media (max-width: 860px) {
        .eng-field-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            position: sticky;
            top: 0;
            z-index: 15;
        }
        .eng-field-form .attr-row,
        .eng-field-form .check-row {
            grid-template-columns: 1fr !important;
        }
        .eng-field-form .check-row .rm,
        .eng-field-form .attr-row .rm {
            justify-self: end;
        }
        .eng-field-form section > div[style*="grid-template-columns: 1fr 1fr"],
        .eng-field-form section > div[style*="grid-template-columns:1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
        .eng-sticky-actions {
            position: sticky;
            top: 0;
            z-index: 15;
            background: rgba(248, 250, 252, 0.96);
            padding: 0.55rem;
            margin: 0 0 0.85rem;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            backdrop-filter: blur(6px);
        }
        .eng-sticky-actions a,
        .eng-sticky-actions button {
            flex: 1 1 auto;
            text-align: center;
            min-height: 2.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .eng-desktop-actions { display: none !important; }
    }
    @media (min-width: 861px) {
        .eng-mobile-actions { display: none !important; }
    }
</style>
