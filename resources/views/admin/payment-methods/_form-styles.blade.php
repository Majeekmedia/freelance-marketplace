<style>
    /* Shared styles for the payment method create/edit forms. */
    .pm-form .field{margin-bottom:1rem}
    .pm-form .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .pm-form .field .optional{color:var(--ink-3);font-weight:400}
    .pm-form .field input,
    .pm-form .field select,
    .pm-form .field textarea{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);
        border-radius:6px;font:inherit;font-size:.9rem;background:#fff;
        color:var(--ink);min-width:0;
    }
    .pm-form .field input:focus,
    .pm-form .field select:focus,
    .pm-form .field textarea:focus{
        outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest);
    }
    .pm-form .hint{font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0;line-height:1.5}

    .pm-form label,
    .pm-form fieldset label{
        display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);
        margin-bottom:.35rem;
    }

    .pm-group{
        border:1px solid var(--rule);
        border-radius:10px;
        padding:1rem 1.1rem 1.15rem;
        margin:0 0 1rem;
        background:var(--paper);
    }
    .pm-group legend{
        font-size:.72rem;letter-spacing:.07em;text-transform:uppercase;
        color:var(--ink-3);font-weight:600;padding:0 .4rem;
    }
    .pm-group .duo{margin-bottom:0}

    .pm-check{
        display:flex;align-items:flex-start;gap:.55rem;
        font-size:.85rem;color:var(--ink-2);margin-bottom:1.1rem;
        cursor:pointer;line-height:1.45;
    }
    .pm-check input{margin:.15rem 0 0;width:16px;height:16px;flex:none}

    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}
    @media (max-width:560px){
        .form-actions .btn{flex:1}
    }
</style>
