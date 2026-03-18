<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root{
            --text: #163225;
            --text-soft: #2f4b3c;
            --muted: #6f7d68;
            --bg: #f4efe3;
            --bg-soft: #fbf6ea;
            --surface: #fffdf7;
            --surface-soft: #fbfaf3;
            --surface-muted: #f2ede0;
            --sidebar-bg: linear-gradient(180deg, #fff8ec 0%, #f2f7ea 54%, #e9f4ee 100%);
            --content-bg: linear-gradient(180deg, rgba(255,253,248,.78) 0%, rgba(247,252,245,.97) 58%, rgba(243,237,224,.98) 100%);
            --line: rgba(70, 102, 84, .13);
            --line-strong: rgba(29, 88, 61, .22);
            --brand: #1d9b6c;
            --brand-strong: #127451;
            --brand-soft: #e3f8ee;
            --brand-soft-2: #cfeee0;
            --nav-active: linear-gradient(180deg, #2e5443 0%, #1f3a2f 100%);
            --danger: #b94d4d;
            --warning: #a56d18;
            --shadow: 0 22px 50px rgba(22, 52, 37, .11);
            --shadow-soft: 0 12px 28px rgba(22, 52, 37, .08);
            --radius-xl: 30px;
            --radius-lg: 24px;
            --radius-md: 18px;
            --ink: var(--text);
            --ink-soft: var(--text-soft);
            --border: var(--line);
            --card: var(--surface);
            --thead: var(--surface-muted);
            --row-alt: #fbfdf9;
            --row-hover: rgba(77, 138, 102, .04);
        }

        *{ box-sizing: border-box; }
        html, body{ min-height: 100%; }
        body{
            margin: 0;
            color: var(--text);
            font-family: "IBM Plex Sans", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 155, 108, .18), transparent 26%),
                radial-gradient(circle at top right, rgba(227, 194, 113, .16), transparent 24%),
                linear-gradient(180deg, #fffaf0 0%, var(--bg-soft) 48%, var(--bg) 100%);
        }
        body.inv-busy{ cursor: progress; }

        a{
            color: inherit;
            text-decoration: none;
        }

        .inv-shell{
            min-height: 100vh;
            display: grid;
            grid-template-columns: 292px minmax(0, 1fr);
            gap: 22px;
            padding: 20px;
            transition: grid-template-columns .18s ease;
        }
        .inv-shell.inv-sidebar-collapsed{
            grid-template-columns: 102px minmax(0, 1fr);
        }

        .inv-overlay{
            display: none;
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(20, 32, 22, .22);
            backdrop-filter: blur(4px);
        }
        .inv-overlay.show{ display: block; }

        .inv-sidebar{
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            padding: 16px;
            border-radius: 32px;
            border: 1px solid var(--line);
            background: var(--sidebar-bg);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: padding .18s ease, transform .18s ease;
        }
        .inv-sidebar::before{
            content: "";
            position: absolute;
            inset: -10% auto auto 58%;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(29, 155, 108, .18), transparent 68%);
            pointer-events: none;
        }
        .inv-sidebar-head{
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }
        .inv-sidebar-close{
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255,255,255,.88);
            color: var(--text);
            align-items: center;
            justify-content: center;
        }
        .inv-brand{
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .inv-logo{
            width: 44px;
            height: 44px;
            border-radius: 15px;
            background: linear-gradient(180deg, #ffffff 0%, #f4f7ee 100%);
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            letter-spacing: .06em;
            color: var(--text);
            box-shadow: 0 14px 24px rgba(29, 155, 108, .12);
            flex: 0 0 44px;
        }
        .inv-brand-text{
            min-width: 0;
        }
        .inv-brand-text strong{
            display: block;
            font-family: "Space Grotesk", sans-serif;
            font-size: 20px;
            line-height: 1.05;
            letter-spacing: -.03em;
        }

        .inv-sidebar-nav{
            position: relative;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }
        .inv-section{
            margin: 18px 8px 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .inv-link{
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 6px 0;
            padding: 11px 12px;
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-soft);
            border: 1px solid transparent;
            transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
            font-size: 14px;
            font-weight: 700;
        }
        .inv-link:hover{
            background: rgb(255, 255, 255);
            border-color: var(--line);
            transform: translateX(2px);
        }
        .inv-link.active{
            background: var(--nav-active);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 14px 24px rgba(55, 63, 56, .18);
        }
        .inv-ico{
            width: 36px;
            height: 36px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.88);
            color: var(--text-soft);
            flex: 0 0 36px;
            transition: inherit;
        }
        .inv-link.active .inv-ico{
            border-color: rgba(255,255,255,.12);
            background: rgba(255,255,255,.16);
            color: #ffffff;
        }
        .inv-link-label{
            min-width: 0;
            white-space: nowrap;
        }

        .inv-shell.inv-sidebar-collapsed .inv-sidebar{
            padding-left: 12px;
            padding-right: 12px;
        }
        .inv-shell.inv-sidebar-collapsed .inv-brand{
            justify-content: center;
        }
        .inv-shell.inv-sidebar-collapsed .inv-brand-text,
        .inv-shell.inv-sidebar-collapsed .inv-section,
        .inv-shell.inv-sidebar-collapsed .inv-link-label{
            display: none;
        }
        .inv-shell.inv-sidebar-collapsed .inv-link{
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        .inv-shell.inv-sidebar-collapsed .inv-sidebar-nav{
            overflow: visible;
        }

        .inv-content{
            min-width: 0;
            min-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding: 16px;
            border-radius: 34px;
            border: 1px solid rgba(255,255,255,.72);
            background: var(--content-bg);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.88),
                0 28px 52px rgba(22, 52, 37, .10);
        }
        .inv-topbar{
            position: sticky;
            top: 16px;
            z-index: 20;
        }
        .inv-topbar-inner{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            min-width: 0;
            padding: 14px 18px;
            border-radius: 28px;
            border: 1px solid var(--line);
            background: rgba(255,251,244,.9);
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
        }
        .inv-topbar-left,
        .inv-topbar-right{
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .inv-topbar-left{
            flex: 1 1 auto;
        }
        .inv-topbar-right{
            flex: 0 0 auto;
            justify-content: flex-end;
        }
        .inv-toggle{
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #ffffff 0%, #f6f7ef 100%);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 20px rgba(29, 155, 108, .10);
        }
        .inv-appname{
            font-family: "Space Grotesk", sans-serif;
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -.03em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .inv-datepill,
        .inv-userpill{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.94);
            box-shadow: 0 10px 18px rgba(28, 46, 34, .05);
            max-width: 100%;
        }
        .inv-datepill{
            font-size: 13px;
            font-weight: 700;
            color: var(--text-soft);
        }
        .inv-userpill{
            position: relative;
            padding: 0;
            overflow: visible;
        }
        .inv-account{
            position: relative;
        }
        .inv-account summary{
            list-style: none;
        }
        .inv-account summary::-webkit-details-marker{
            display: none;
        }
        .inv-account-toggle{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 8px 14px;
            border-radius: 999px;
            cursor: pointer;
            max-width: 100%;
        }
        .inv-account-toggle:hover{
            background: rgba(255,255,255,.88);
        }
        .inv-avatar{
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: linear-gradient(180deg, #5f685f 0%, #434b43 100%);
            color: #ffffff;
            display: grid;
            place-items: center;
            font-family: "IBM Plex Mono", monospace;
            font-size: 12px;
            font-weight: 600;
        }
        .inv-usertext strong{
            display: block;
            font-size: 13px;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .inv-usertext span{
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .inv-account-chevron{
            color: var(--muted);
            font-size: 12px;
        }
        .inv-account-menu{
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            min-width: 240px;
            padding: 10px;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,253,248,.99) 0%, rgba(247,252,245,.98) 100%);
            box-shadow: 0 18px 32px rgba(22, 52, 37, .12);
        }
        .inv-account-head{
            padding: 10px 12px 12px;
            border-bottom: 1px solid var(--line);
        }
        .inv-account-name{
            font-weight: 700;
            font-size: 14px;
        }
        .inv-account-role{
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }
        .inv-account-links{
            display: grid;
            gap: 6px;
            padding-top: 10px;
        }
        .inv-account-link,
        .inv-account-links button{
            width: 100%;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 16px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-soft);
            text-align: left;
            font-size: 13px;
            font-weight: 700;
        }
        .inv-account-link:hover,
        .inv-account-links button:hover{
            border-color: var(--line);
            background: rgba(255,255,255,.82);
        }
        .inv-account-links form{
            margin: 0;
        }
        .inv-main{
            padding: 0 2px 8px;
        }

        .inv-card{
            background: linear-gradient(180deg, rgba(255,253,248,.99) 0%, rgba(247,252,245,.98) 100%);
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
        }
        .inv-page{
            display: grid;
            gap: 18px;
        }
        .inv-page-header{
            display: grid;
            gap: 18px;
        }
        .inv-page-header-top{
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
        }
        .inv-page-header-card{
            padding: 22px 24px;
            background:
                radial-gradient(circle at top right, rgba(29, 155, 108, .12), transparent 22%),
                radial-gradient(circle at bottom left, rgba(227, 194, 113, .12), transparent 24%),
                linear-gradient(180deg, rgba(255,253,248,.99) 0%, rgba(244,250,241,.99) 100%);
        }
        .inv-page-titles{
            min-width: 0;
        }
        .inv-h1{
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(30px, 4vw, 40px);
            line-height: .98;
            letter-spacing: -.05em;
        }
        .inv-sub{
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .inv-page-actions{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .inv-page-toolbar{
            display: grid;
            gap: 14px;
            padding-top: 18px;
            border-top: 1px solid var(--line);
        }
        .inv-page-body{
            display: grid;
            gap: 18px;
        }

        .inv-alert{
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 15px 16px;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.96);
            box-shadow: var(--shadow-soft);
        }
        .inv-alert.warning{
            border-color: rgba(165, 109, 24, .22);
            background: linear-gradient(180deg, #fffdf8 0%, #fff8eb 100%);
        }
        .inv-alert.warning .inv-alert-ico{
            background: linear-gradient(180deg, #b17d24 0%, #8f6114 100%);
        }
        .inv-alert-ico{
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(77, 138, 102, .20);
        }
        .inv-alert strong{
            display: block;
            margin-bottom: 4px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 15px;
        }
        .inv-alert ul{
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .inv-toast-overlay{
            position: fixed;
            inset: 0;
            z-index: 1190;
            background: rgba(12, 20, 16, .32);
            backdrop-filter: blur(8px);
        }
        .inv-toast-layer{
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            pointer-events: none;
        }
        .inv-toast-stack{
            display: grid;
            gap: 12px;
            max-width: min(420px, calc(100vw - 40px));
            pointer-events: auto;
        }
        .inv-toast{
            pointer-events: auto;
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
            padding: 14px 14px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.98);
            box-shadow: 0 18px 36px rgba(22, 52, 37, .14);
            animation: inv-toast-in .2s ease;
        }
        .inv-toast.hide{
            animation: inv-toast-out .2s ease forwards;
        }
        .inv-toast-icon{
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            box-shadow: 0 8px 18px rgba(77, 138, 102, .20);
        }
        .inv-toast-title{
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 14px;
            letter-spacing: -.01em;
        }
        .inv-toast-message{
            margin-top: 4px;
            color: var(--text-soft);
            font-size: 13px;
            line-height: 1.4;
        }
        .inv-toast-close{
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
        }
        .inv-toast.success{
            border-color: rgba(18, 116, 81, .22);
        }
        .inv-toast.success .inv-toast-icon{
            background: linear-gradient(180deg, #1aa46c 0%, #0f6b47 100%);
        }
        .inv-toast.warning{
            border-color: rgba(165, 109, 24, .28);
            background: linear-gradient(180deg, #fffdf8 0%, #fff6e8 100%);
        }
        .inv-toast.warning .inv-toast-icon{
            background: linear-gradient(180deg, #b17d24 0%, #8f6114 100%);
        }
        .inv-toast.error{
            border-color: rgba(185, 77, 77, .3);
            background: linear-gradient(180deg, #fff9f9 0%, #fff0f0 100%);
        }
        .inv-toast.error .inv-toast-icon{
            background: linear-gradient(180deg, #d25d5d 0%, #a33434 100%);
        }
        .inv-toast.info{
            border-color: rgba(52, 112, 170, .26);
            background: linear-gradient(180deg, #f6fbff 0%, #eef6ff 100%);
        }
        .inv-toast.info .inv-toast-icon{
            background: linear-gradient(180deg, #4b84c6 0%, #2f5f98 100%);
        }
        @keyframes inv-toast-in{
            from{ opacity:0; transform: translateY(-8px); }
            to{ opacity:1; transform: translateY(0); }
        }
        @keyframes inv-toast-out{
            from{ opacity:1; transform: translateY(0); }
            to{ opacity:0; transform: translateY(-8px); }
        }
        @media (max-width: 767.98px){
            .inv-toast-stack{
                max-width: calc(100vw - 28px);
            }
        }

        .inv-table-card,
        .inv-panel,
        .inv-form-card,
        .inv-receipt-head,
        .inv-lines-card,
        .inv-alert-hero,
        .router-summary{
            overflow: hidden;
        }
        .inv-table-head,
        .inv-panel-head,
        .inv-form-head,
        .inv-form-actions,
        .inv-table-foot{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
        }
        .inv-form-actions,
        .inv-table-foot{
            border-top: 1px solid var(--line);
            border-bottom: 0;
        }
        .inv-table-title,
        .inv-panel-title,
        .inv-form-title{
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.03em;
        }
        .inv-table-sub,
        .inv-panel-sub,
        .inv-form-sub{
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.4;
        }
        .inv-table-body,
        .inv-panel-body,
        .inv-form-body{
            padding: 18px;
        }
        .inv-table-tools{
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }

        .table{
            border-color: var(--line) !important;
            margin-bottom: 0;
            width: 100% !important;
            min-width: max-content;
            table-layout: auto;
        }
        .table thead th{
            background: linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%) !important;
            color: var(--muted);
            font-size: 11px;
            font-family: "IBM Plex Mono", monospace;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .12em;
            border-bottom: 1px solid var(--line) !important;
            padding-top: 14px !important;
            padding-bottom: 14px !important;
        }
        .table td{
            vertical-align: middle;
            border-color: var(--line) !important;
            color: var(--text-soft);
            padding-top: 14px !important;
            padding-bottom: 14px !important;
        }
        .table-hover tbody tr:hover{
            background: rgba(29, 155, 108, .05);
        }
        .table-responsive,
        .router-table-wrap{
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }
        .table-responsive > .table,
        .router-table-wrap > .table{
            width: 100% !important;
        }

        .inv-empty{
            padding: 30px 22px;
            text-align: center;
        }
        .inv-empty-ico{
            width: 58px;
            height: 58px;
            margin: 0 auto 12px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #ffffff 0%, #edf4ea 100%);
            box-shadow: var(--shadow-soft);
        }
        .inv-empty-title{
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 20px;
        }
        .inv-empty-sub{
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .inv-chip{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--text-soft);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .inv-divider{
            height: 1px;
            background: var(--line);
            margin: 14px 0;
        }
        .inv-muted{ color: var(--muted) !important; }

        .form-control,
        .form-select{
            min-height: 48px;
            border-radius: 18px;
            border: 1px solid var(--line-strong);
            background: rgba(255,255,255,.98);
            color: var(--text);
            padding: .78rem .95rem;
            box-shadow: none;
        }
        textarea.form-control{ min-height: 110px; }
        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus{
            border-color: rgba(77, 138, 102, .45);
            box-shadow: 0 0 0 .16rem rgba(77, 138, 102, .10);
        }
        .form-check-input{
            border-color: var(--line-strong);
        }
        .form-check-input:checked{
            background-color: var(--brand-strong);
            border-color: var(--brand-strong);
        }
        .form-label{
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .btn{
            border-radius: 999px;
            min-height: 42px;
            padding: .65rem 1rem;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: .02em;
            box-shadow: none;
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease;
        }
        .btn:hover{
            transform: translateY(-1px);
        }
        .btn-sm{
            min-height: 38px;
            padding: .46rem .82rem;
            font-size: 12px;
        }
        .btn-dark,
        .btn-primary{
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            border-color: var(--brand-strong);
            color: #ffffff;
            box-shadow: 0 14px 24px rgba(77, 138, 102, .18);
        }
        .btn-dark:hover,
        .btn-dark:focus,
        .btn-primary:hover,
        .btn-primary:focus{
            background: linear-gradient(180deg, #17865d 0%, #105b40 100%);
            border-color: #105b40;
            color: #ffffff;
            box-shadow: 0 18px 30px rgba(29, 155, 108, .24);
        }
        .btn-outline-dark,
        .btn-outline-secondary,
        .btn-outline-primary{
            background: rgba(255,255,255,.98);
            border-color: var(--line-strong);
            color: var(--text);
        }
        .btn-outline-primary{
            color: var(--brand-strong);
            border-color: rgba(77, 138, 102, .28);
        }
        .btn-outline-dark:hover,
        .btn-outline-secondary:hover{
            background: #ffffff;
            border-color: rgba(77, 138, 102, .30);
            color: var(--text);
            box-shadow: 0 10px 18px rgba(28, 46, 34, .08);
        }
        .btn-outline-primary:hover{
            background: var(--brand-soft);
            border-color: rgba(77, 138, 102, .42);
            color: var(--brand-strong);
            box-shadow: 0 10px 18px rgba(77, 138, 102, .12);
        }
        .btn-outline-danger{
            background: #ffffff;
            color: var(--danger);
            border-color: rgba(185, 77, 77, .26);
        }
        .btn-outline-danger:hover{
            background: #fff5f5;
            border-color: rgba(185, 77, 77, .38);
            color: #983e3e;
        }

        .action-menu{
            position: relative;
            display: inline-block;
        }
        .action-menu[open]{
            z-index: 1200;
        }
        .action-menu > summary{
            list-style: none;
            cursor: pointer;
            user-select: none;
        }
        .action-menu > summary::-webkit-details-marker{
            display: none;
        }
        .action-menu-list{
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            z-index: 1300;
            min-width: 220px;
            padding: 6px;
            display: grid;
            gap: 4px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.98);
            box-shadow: 0 22px 38px rgba(22, 52, 37, .16);
            backdrop-filter: blur(14px);
        }
        .action-menu-item{
            width: 100%;
            display: block;
            padding: 9px 11px;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: var(--text-soft);
            text-align: left;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            cursor: pointer;
        }
        .action-menu-item:hover{
            background: linear-gradient(180deg, #fff9ef 0%, #eef8f0 100%);
            color: var(--text);
        }
        .action-menu-item.is-highlight{
            color: var(--brand-strong);
        }
        .action-menu-item.is-disabled{
            background: #f6f8f3;
            color: #95a199;
            cursor: not-allowed;
        }

        .badge{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: .42rem .68rem;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            border: 1px solid transparent;
        }
        .badge.bg-dark,
        .badge.bg-primary,
        .badge.bg-success{
            background: var(--brand-soft) !important;
            color: var(--brand-strong) !important;
            border-color: rgba(77, 138, 102, .20);
        }
        .badge.bg-danger{
            background: #fff1f1 !important;
            color: #a33d3d !important;
            border-color: rgba(185, 77, 77, .18);
        }
        .badge.bg-warning{
            background: #fff8e7 !important;
            color: #94610a !important;
            border-color: rgba(165, 109, 24, .18);
        }
        .badge.bg-secondary,
        .badge.bg-info{
            background: #f1f5f0 !important;
            color: var(--text-soft) !important;
            border-color: var(--line);
        }
        .text-primary{ color: var(--brand-strong) !important; }

        .pagination{
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
        }
        .pagination .page-link{
            border-radius: 999px !important;
            border-color: var(--line) !important;
            color: var(--text) !important;
            background: #ffffff !important;
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 14px rgba(28, 46, 34, .05);
        }
        .pagination .page-item.active .page-link{
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%) !important;
            color: #ffffff !important;
            border-color: var(--brand-strong) !important;
        }

        .inv-panel-action,
        .router-tab,
        .inv-pillbtn,
        .inv-linkpill{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 40px;
            padding: .55rem .95rem;
            background: #ffffff !important;
            color: var(--text) !important;
            border: 1px solid var(--line-strong) !important;
            border-radius: 999px !important;
            box-shadow: 0 10px 20px rgba(28, 46, 34, .06) !important;
            text-decoration: none;
        }
        .router-tab.active,
        .inv-pillbtn--dark{
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%) !important;
            border-color: var(--brand-strong) !important;
            color: #ffffff !important;
        }
        .inv-stat-card::before{
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%) !important;
            opacity: 1 !important;
        }
        .inv-input-invalid{
            border-color: rgba(185, 77, 77, .45) !important;
            box-shadow: 0 0 0 .16rem rgba(185, 77, 77, .10) !important;
        }
        .inv-error,
        .text-danger{
            color: var(--danger) !important;
        }
        .inv-hint,
        .inv-actions-right,
        .router-cell-sub,
        .inv-items-sub,
        .inv-subline{
            color: var(--muted) !important;
        }
        .inv-btn-wide{
            border-radius: 999px !important;
        }

        .inv-progress{
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            opacity: 0;
            pointer-events: none;
            z-index: 1000;
            transition: opacity .18s ease;
            background: rgba(29, 155, 108, .12);
        }
        .inv-progress.show{ opacity: 1; }
        .inv-progress-bar{
            width: 36%;
            height: 100%;
            background: linear-gradient(90deg, rgba(29, 155, 108, 0) 0%, #1d9b6c 30%, #83d1a8 55%, #127451 82%, rgba(18, 116, 81, 0) 100%);
            filter: drop-shadow(0 0 10px rgba(29, 155, 108, .30));
            animation: invProgressSlide 1.15s ease-in-out infinite;
            transform: translateX(-45%);
        }
        @keyframes invProgressSlide{
            0%{ transform: translateX(-45%) scaleX(.8); }
            55%{ transform: translateX(130%) scaleX(1); }
            100%{ transform: translateX(220%) scaleX(.86); }
        }

        .inv-skeleton{
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            background: rgba(92, 116, 96, .11);
        }
        .inv-skeleton::after{
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.76), transparent);
            transform: translateX(-100%);
            animation: invShimmer 1.1s infinite;
        }
        .inv-skel-row{ height: 14px; margin: 10px 0; }
        .inv-skel-row.sm{ height: 10px; }
        .inv-skel-row.lg{ height: 18px; }
        @keyframes invShimmer{ to{ transform: translateX(100%); } }

        @media (max-width: 1199.98px){
            .inv-page-header{
                align-items: flex-start;
                flex-direction: column;
            }
            .inv-page-actions{
                justify-content: flex-start;
            }
        }

        @media (max-width: 991.98px){
            .inv-shell{
                grid-template-columns: 1fr !important;
                padding: 14px;
                gap: 14px;
            }
            .inv-sidebar{
                position: fixed;
                top: 14px;
                left: 14px;
                width: min(300px, calc(100vw - 28px));
                height: calc(100vh - 28px);
                transform: translateX(-108%);
                z-index: 50;
            }
            .inv-sidebar.open{
                transform: translateX(0);
            }
            .inv-sidebar-close{
                display: inline-flex;
            }
            .inv-content{
                min-height: auto;
                padding: 12px;
                border-radius: 28px;
            }
            .inv-topbar{
                position: static;
                top: auto;
            }
            .inv-topbar-inner{
                padding: 12px 14px;
                border-radius: 22px;
            }
        }

        @media (max-width: 575.98px){
            .inv-topbar-inner{
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px;
                align-items: center;
            }
            .inv-topbar-left,
            .inv-topbar-right{
                width: auto;
            }
            .inv-topbar-right{
                justify-content: flex-end;
            }
            .inv-userpill{
                min-width: 0;
                max-width: 100%;
            }
            .inv-account-toggle{
                min-height: 42px;
                padding: 6px 10px;
                gap: 8px;
            }
            .inv-avatar{
                width: 32px;
                height: 32px;
                font-size: 11px;
            }
            .inv-usertext strong{
                max-width: 112px;
                font-size: 12px;
            }
            .inv-usertext span{
                display: none;
            }
            .inv-datepill{
                display: none;
            }
            .inv-appname{
                font-size: 18px;
            }
            .inv-account-menu{
                position: fixed;
                top: 72px;
                right: 12px;
                left: 12px;
                min-width: 0;
                width: auto;
                max-width: none;
            }
            .inv-page-header-card{
                padding: 20px;
            }
            .inv-h1{
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

@php
    $access = \App\Modules\Inventory\Support\InventoryAccess::class;
    $u = auth('inventory')->user();
    $name = $u?->name ?? 'Inventory';
    $initials = collect(explode(' ', trim($name)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $roleLabel = $access::roleLabel($u?->inventory_role);
    $path = request()->path();
    $isActive = fn (string $needle) => str_contains($path, $needle);
    $can = fn (string $permission) => $access::allows($u, $permission);
@endphp

<div class="inv-shell" id="invShell">
    <div class="inv-overlay" id="invOverlay" onclick="invCloseSidebar()"></div>

    <aside class="inv-sidebar" id="invSidebar">
        <div class="inv-sidebar-head">
            <div class="inv-brand">
                <div class="inv-logo">IN</div>
                <div class="inv-brand-text">
                    <strong>Inventory</strong>
                </div>
            </div>
            <button class="inv-sidebar-close" type="button" onclick="invCloseSidebar()" aria-label="Close sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="inv-sidebar-nav">
            @if($can('dashboard.view') || $can('tech_inventory.view') || $can('routers.view') || $can('items.view') || $can('item_groups.view'))
                <div class="inv-section">Core</div>
            @endif

            @if($can('dashboard.view'))
                <a class="inv-link {{ $isActive('inventory/dashboard') || $path === 'inventory' ? 'active' : '' }}" href="{{ route('inventory.dashboard') }}" title="Dashboard">
                    <span class="inv-ico"><i class="bi bi-speedometer2"></i></span>
                    <span class="inv-link-label">Dashboard</span>
                </a>
            @endif

            @if($can('tech_inventory.view') && !$access::isAdmin($u))
                <a class="inv-link {{ $isActive('inventory/tech/dashboard') || $isActive('inventory/tech/items') ? 'active' : '' }}" href="{{ route('inventory.tech.dashboard') }}" title="My Dashboard">
                    <span class="inv-ico"><i class="bi bi-briefcase"></i></span>
                    <span class="inv-link-label">My Dashboard</span>
                </a>
            @endif

            @if($can('items.view'))
                <a class="inv-link {{ $isActive('inventory/items') ? 'active' : '' }}" href="{{ route('inventory.items.index') }}" title="Items">
                    <span class="inv-ico"><i class="bi bi-box-seam"></i></span>
                    <span class="inv-link-label">Items</span>
                </a>
            @endif

            @if($can('routers.view'))
                <a class="inv-link {{ $isActive('inventory/routers') ? 'active' : '' }}" href="{{ route('inventory.routers.index') }}" title="Routers">
                    <span class="inv-ico"><i class="bi bi-hdd-network"></i></span>
                    <span class="inv-link-label">Routers</span>
                </a>
            @endif

            @if($can('item_groups.view'))
                <a class="inv-link {{ $isActive('inventory/item-groups') ? 'active' : '' }}" href="{{ route('inventory.item-groups.index') }}" title="Item Groups">
                    <span class="inv-ico"><i class="bi bi-grid-1x2"></i></span>
                    <span class="inv-link-label">Item Groups</span>
                </a>
            @endif

            @if($can('receipts.view') || $can('assignments.view') || $can('team_assignments.view'))
                <div class="inv-section">Stock</div>
            @endif

            @if($can('receipts.view'))
                <a class="inv-link {{ $isActive('inventory/receipts') ? 'active' : '' }}" href="{{ route('inventory.receipts.index') }}" title="Receipts">
                    <span class="inv-ico"><i class="bi bi-inbox"></i></span>
                    <span class="inv-link-label">Receipts</span>
                </a>
            @endif

            @if($can('assignments.view'))
                <a class="inv-link {{ $isActive('inventory/assignments') ? 'active' : '' }}" href="{{ route('inventory.assignments.index') }}" title="Tech Assignments">
                    <span class="inv-ico"><i class="bi bi-person-check"></i></span>
                    <span class="inv-link-label">Tech Assignments</span>
                </a>
            @endif

            @if($can('team_assignments.view'))
                <a class="inv-link {{ $isActive('inventory/team-assignments') ? 'active' : '' }}" href="{{ route('inventory.team_assignments.index') }}" title="Team Assignments">
                    <span class="inv-ico"><i class="bi bi-people"></i></span>
                    <span class="inv-link-label">Team Assignments</span>
                </a>
            @endif

            @if($can('deployments.view') || $can('team_deployments.view') || $can('movements.view'))
                <div class="inv-section">Field</div>
            @endif

            @if($can('deployments.view'))
                <a class="inv-link {{ $isActive('inventory/deployments') ? 'active' : '' }}" href="{{ route('inventory.deployments.index') }}" title="Deployments">
                    <span class="inv-ico"><i class="bi bi-geo-alt"></i></span>
                    <span class="inv-link-label">Deployments</span>
                </a>
            @endif

            @if($can('team_deployments.view'))
                <a class="inv-link {{ $isActive('inventory/team-deployments') ? 'active' : '' }}" href="{{ route('inventory.team_deployments.index') }}" title="Team Deployments">
                    <span class="inv-ico"><i class="bi bi-pin-map"></i></span>
                    <span class="inv-link-label">Team Deployments</span>
                </a>
            @endif

            @if($can('movements.view'))
                <a class="inv-link {{ $isActive('inventory/movements') ? 'active' : '' }}" href="{{ route('inventory.movements.index') }}" title="Transfers / Returns">
                    <span class="inv-ico"><i class="bi bi-arrow-left-right"></i></span>
                    <span class="inv-link-label">Transfers / Returns</span>
                </a>
            @endif

            @if($can('logs.view') || $can('teams.view'))
                <div class="inv-section">Audit</div>
            @endif

            @if($can('logs.view'))
                <a class="inv-link {{ $isActive('inventory/logs') ? 'active' : '' }}" href="{{ route('inventory.logs.index') }}" title="Logs">
                    <span class="inv-ico"><i class="bi bi-journal-text"></i></span>
                    <span class="inv-link-label">Logs</span>
                </a>
            @endif

            @if($can('teams.view'))
                <a class="inv-link {{ $isActive('inventory/teams') ? 'active' : '' }}" href="{{ route('inventory.teams.index') }}" title="Teams">
                    <span class="inv-ico"><i class="bi bi-shield-lock"></i></span>
                    <span class="inv-link-label">Teams</span>
                </a>
            @endif
        </div>
    </aside>

    <div class="inv-content">
        <div class="inv-topbar">
            <div class="inv-topbar-inner">
                <div class="inv-topbar-left">
                    <button class="inv-toggle" type="button" onclick="invToggleSidebar()" aria-label="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div class="inv-appname">Inventory</div>
                </div>

                <div class="inv-topbar-right">
                    <div class="inv-datepill">
                        <i class="bi bi-calendar3"></i>
                        <span>{{ now()->format('D, d M Y') }}</span>
                    </div>

                    <div class="inv-userpill">
                        <details class="inv-account">
                            <summary class="inv-account-toggle">
                                <div class="inv-avatar">{{ $initials ?: 'IN' }}</div>
                                <div class="inv-usertext">
                                    <strong>{{ $name }}</strong>
                                    <span>{{ $roleLabel }}</span>
                                </div>
                                <i class="bi bi-chevron-down inv-account-chevron"></i>
                            </summary>

                            <div class="inv-account-menu">
                                <div class="inv-account-head">
                                    <div class="inv-account-name">{{ $name }}</div>
                                    <div class="inv-account-role">{{ $roleLabel }}</div>
                                </div>

                                <div class="inv-account-links">
                                    <a class="inv-account-link" href="{{ route('inventory.auth.password.change') }}" data-inv-loading>
                                        <i class="bi bi-key"></i>
                                        <span>Change Password</span>
                                    </a>

                                    @if($access::isAdmin($u))
                                        <a class="inv-account-link" href="{{ route('inventory.settings.index') }}" data-inv-loading>
                                            <i class="bi bi-sliders"></i>
                                            <span>Access Settings</span>
                                        </a>

                                        <a class="inv-account-link" href="{{ route('inventory.auth.switch.tech') }}">
                                            <i class="bi bi-person-badge"></i>
                                            <span>Login as Technician</span>
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('inventory.auth.logout') }}" data-inv-loading>
                                        @csrf
                                        <button type="submit">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span>Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <div class="inv-main">
            @php
                $toastItems = [];
                if (session('success')) {
                    $toastItems[] = [
                        'type' => 'success',
                        'title' => 'Success',
                        'message' => session('success'),
                        'icon' => 'check2',
                        'timeout' => 4500,
                    ];
                }
                if (session('warning')) {
                    $toastItems[] = [
                        'type' => 'warning',
                        'title' => 'Attention',
                        'message' => session('warning'),
                        'icon' => 'exclamation',
                        'timeout' => 6500,
                    ];
                }
                if (session('info')) {
                    $toastItems[] = [
                        'type' => 'info',
                        'title' => 'Info',
                        'message' => session('info'),
                        'icon' => 'info-lg',
                        'timeout' => 5000,
                    ];
                }
                if (session('error')) {
                    $toastItems[] = [
                        'type' => 'error',
                        'title' => 'Error',
                        'message' => session('error'),
                        'icon' => 'x-lg',
                        'timeout' => 7000,
                    ];
                }
                if ($errors->any()) {
                    $firstError = $errors->first();
                    $extraCount = max(0, $errors->count() - 1);
                    $message = $extraCount > 0 ? ($firstError . ' (+' . $extraCount . ' more)') : $firstError;
                    $toastItems[] = [
                        'type' => 'error',
                        'title' => 'Check the form',
                        'message' => $message,
                        'icon' => 'slash-circle',
                        'timeout' => 8000,
                    ];
                }
            @endphp

            @if(!empty($toastItems))
                <div class="inv-toast-overlay" id="invToastOverlay" aria-hidden="true"></div>
                <div class="inv-toast-layer" id="invToastLayer">
                    <div class="inv-toast-stack">
                        @foreach($toastItems as $toast)
                            <div class="inv-toast {{ $toast['type'] ?? 'info' }}" data-timeout="{{ $toast['timeout'] ?? 5000 }}">
                                <div class="inv-toast-icon">
                                    <i class="bi bi-{{ $toast['icon'] ?? 'info-lg' }}"></i>
                                </div>
                                <div>
                                    <div class="inv-toast-title">{{ $toast['title'] ?? 'Notice' }}</div>
                                    <div class="inv-toast-message">{{ $toast['message'] ?? '' }}</div>
                                </div>
                                <button class="inv-toast-close" type="button" aria-label="Close">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<div class="inv-progress" id="invProgress" aria-hidden="true">
    <div class="inv-progress-bar"></div>
</div>

<script>
    const invShell = document.getElementById('invShell');
    const invSidebar = document.getElementById('invSidebar');
    const invOverlay = document.getElementById('invOverlay');
    const invProgress = document.getElementById('invProgress');
    const invToastLayer = document.getElementById('invToastLayer');
    const invToastOverlay = document.getElementById('invToastOverlay');
    const invSidebarStorageKey = 'inventory.sidebar.collapsed';
    const invAutoFilterSelector = 'form[data-inv-autofilter][method="GET"], form[data-inv-autofilter][method="get"]';

    function invOpenSidebar() {
        if (!invSidebar || !invOverlay) return;
        invSidebar.classList.add('open');
        invOverlay.classList.add('show');
    }

    function invCloseSidebar() {
        if (!invSidebar || !invOverlay) return;
        invSidebar.classList.remove('open');
        invOverlay.classList.remove('show');
    }

    function invApplyDesktopSidebarState(collapsed) {
        if (!invShell || window.innerWidth <= 991) return;
        invShell.classList.toggle('inv-sidebar-collapsed', collapsed);
    }

    if (invToastLayer) {
        invToastLayer.querySelectorAll('.inv-toast').forEach(function (toast) {
            const timeout = Number(toast.dataset.timeout || 5000);
            const closeBtn = toast.querySelector('.inv-toast-close');

            const dismiss = function () {
                if (!toast || toast.classList.contains('hide')) {
                    return;
                }
                toast.classList.add('hide');
                window.setTimeout(function () {
                    toast.remove();
                    if (!invToastLayer.querySelector('.inv-toast')) {
                        invToastLayer.remove();
                        if (invToastOverlay) {
                            invToastOverlay.remove();
                        }
                    }
                }, 220);
            };

            if (closeBtn) {
                closeBtn.addEventListener('click', dismiss);
            }

            if (timeout > 0) {
                window.setTimeout(dismiss, timeout);
            }
        });
    }

    function invToggleSidebar() {
        if (window.innerWidth <= 991) {
            if (invSidebar?.classList.contains('open')) {
                invCloseSidebar();
            } else {
                invOpenSidebar();
            }
            return;
        }

        const collapsed = !invShell.classList.contains('inv-sidebar-collapsed');
        invApplyDesktopSidebarState(collapsed);
        localStorage.setItem(invSidebarStorageKey, collapsed ? '1' : '0');
    }

    function invShowLoader() {
        if (!invProgress) return;
        document.body.classList.add('inv-busy');
        invProgress.classList.add('show');
        invProgress.setAttribute('aria-hidden', 'false');
    }

    function invHideLoader() {
        if (!invProgress) return;
        document.body.classList.remove('inv-busy');
        invProgress.classList.remove('show');
        invProgress.setAttribute('aria-hidden', 'true');
    }

    function invClearAutoFilter(form) {
        if (!form?.dataset.invAutofilterTimer) return;
        window.clearTimeout(Number(form.dataset.invAutofilterTimer));
        delete form.dataset.invAutofilterTimer;
    }

    function invBuildGetUrl(form) {
        const action = form.getAttribute('action') || window.location.pathname;
        const url = new URL(action, window.location.origin);
        const params = new URLSearchParams();

        for (const [key, value] of new FormData(form).entries()) {
            if (typeof value !== 'string') {
                continue;
            }

            if (value === '') {
                continue;
            }

            params.append(key, value);
        }

        url.search = params.toString();

        return url;
    }

    const invAutoFilterFocusKey = 'inventory.autofilter.focus';

    function invRememberAutoFilterTarget(target) {
        if (
            !(target instanceof HTMLInputElement) &&
            !(target instanceof HTMLTextAreaElement) &&
            !(target instanceof HTMLSelectElement)
        ) {
            return;
        }

        if (!target.name) {
            return;
        }

        const payload = {
            path: window.location.pathname,
            name: target.name,
            selectionStart: typeof target.selectionStart === 'number' ? target.selectionStart : null,
            selectionEnd: typeof target.selectionEnd === 'number' ? target.selectionEnd : null,
        };

        try {
            sessionStorage.setItem(invAutoFilterFocusKey, JSON.stringify(payload));
        } catch (_) {
            // Ignore storage failures.
        }
    }

    function invRestoreAutoFilterTarget() {
        try {
            const raw = sessionStorage.getItem(invAutoFilterFocusKey);
            if (!raw) {
                return;
            }

            const payload = JSON.parse(raw);
            sessionStorage.removeItem(invAutoFilterFocusKey);

            if (!payload || payload.path !== window.location.pathname || !payload.name) {
                return;
            }

            const target = document.querySelector(invAutoFilterSelector + ' [name="' + payload.name.replace(/"/g, '\\"') + '"]');
            if (
                !(target instanceof HTMLInputElement) &&
                !(target instanceof HTMLTextAreaElement) &&
                !(target instanceof HTMLSelectElement)
            ) {
                return;
            }

            window.requestAnimationFrame(function () {
                target.focus({ preventScroll: true });

                if (
                    typeof payload.selectionStart === 'number' &&
                    typeof payload.selectionEnd === 'number' &&
                    'setSelectionRange' in target
                ) {
                    target.setSelectionRange(payload.selectionStart, payload.selectionEnd);
                }
            });
        } catch (_) {
            // Ignore storage failures.
        }
    }

    function invAutoSubmitForm(form, target = null) {
        if (!form || form.dataset.invSubmitting === '1') {
            return;
        }

        form.dataset.invSubmitting = '1';
        invClearAutoFilter(form);
        if (target) {
            invRememberAutoFilterTarget(target);
        }
        window.location.replace(invBuildGetUrl(form).toString());
    }

    function invScheduleAutoSubmit(form, target = null, delay = 760) {
        if (!form) {
            return;
        }

        invClearAutoFilter(form);
        form.dataset.invAutofilterTimer = String(window.setTimeout(function () {
            invAutoSubmitForm(form, target);
        }, delay));
    }

    document.addEventListener('DOMContentLoaded', function () {
        invApplyDesktopSidebarState(localStorage.getItem(invSidebarStorageKey) === '1');
        invHideLoader();
        invRestoreAutoFilterTarget();
    });

    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[data-inv-loading]');
        if (!a) {
            return;
        }

        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        if (a.target === '_blank' || a.hasAttribute('download')) {
            return;
        }

        const href = a.getAttribute('href');
        if (href && href !== '#') {
            invShowLoader();
        }
    });

    document.addEventListener('submit', function (e) {
        const f = e.target.closest('form[data-inv-loading]');
        if (f) {
            invShowLoader();
        }
    });

    function invCloseActionMenus(exceptMenu) {
        document.querySelectorAll('details.action-menu[open]').forEach(function (menu) {
            if (exceptMenu && menu === exceptMenu) {
                return;
            }

            menu.removeAttribute('open');
        });
    }

    document.addEventListener('toggle', function (e) {
        const menu = e.target;
        if (!(menu instanceof HTMLDetailsElement) || !menu.matches('details.action-menu')) {
            return;
        }

        if (menu.open) {
            invCloseActionMenus(menu);
        }
    }, true);

    document.addEventListener('click', function (e) {
        const menu = e.target.closest('details.action-menu');
        if (!menu) {
            invCloseActionMenus();
            return;
        }

        const item = e.target.closest('.action-menu-item');
        if (item && item.getAttribute('aria-disabled') !== 'true') {
            window.setTimeout(function () {
                menu.removeAttribute('open');
            }, 0);
        }
    });

    document.addEventListener('input', function (e) {
        const target = e.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        const form = target.closest(invAutoFilterSelector);
        if (!form || target.type === 'hidden' || target.type === 'checkbox' || target.type === 'radio' || target.type === 'date') {
            return;
        }

        invScheduleAutoSubmit(form, target, 760);
    });

    document.addEventListener('change', function (e) {
        const target = e.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const form = target.closest(invAutoFilterSelector);
        if (!form) {
            return;
        }

        if (target instanceof HTMLSelectElement) {
            invScheduleAutoSubmit(form, target, 120);
            return;
        }

        if (target instanceof HTMLInputElement && (target.type === 'checkbox' || target.type === 'radio' || target.type === 'date')) {
            invScheduleAutoSubmit(form, target, 120);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            invCloseActionMenus();
        }

        const target = e.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (e.key !== 'Enter') {
            return;
        }

        const form = target.closest(invAutoFilterSelector);
        if (!form || target instanceof HTMLTextAreaElement) {
            return;
        }

        e.preventDefault();
        invAutoSubmitForm(form, target);
    });

    window.addEventListener('pageshow', invHideLoader);

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991) {
            invCloseSidebar();
            invApplyDesktopSidebarState(localStorage.getItem(invSidebarStorageKey) === '1');
        } else if (invShell) {
            invShell.classList.remove('inv-sidebar-collapsed');
        }
    });
</script>

@include('partials.back_iconize')

</body>
</html>
