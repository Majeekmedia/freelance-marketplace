<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ \App\Support\SiteSettings::brandName() }} — {{ \App\Support\SiteSettings::tagline() }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ============================================================
   TOKENS — trust-first, editorial-credible
   Dials: VARIANCE 6 / MOTION 3 / DENSITY 5
   ============================================================ */
:root{
  --paper:#FBFAF7;
  --paper-2:#F4F1E9;
  --paper-3:#EDE9DE;
  --ink:#16140F;
  --ink-2:#4C473C;
  --ink-3:#7E7869;
  --rule:#E1DBCD;
  --rule-2:#CFC8B6;

  --forest:#1C4A3B;
  --forest-2:#2A6450;
  --forest-wash:#E9F0EC;
  --amber:#8A5A16;
  --amber-wash:#F7EEDC;

  --radius-sm:6px;
  --radius:10px;
  --radius-lg:14px;

  --serif:"Instrument Serif",Georgia,"Times New Roman",serif;
  --sans:"Instrument Sans",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;

  --gut:clamp(20px,5vw,72px);
}

*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{
  margin:0;background:var(--paper);color:var(--ink);
  font-family:var(--sans);font-size:16px;line-height:1.55;
  -webkit-font-smoothing:antialiased;
}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit;background:none;border:none;cursor:pointer}

:focus-visible{outline:2px solid var(--forest);outline-offset:3px;border-radius:3px}

.sr-only{
  position:absolute;width:1px;height:1px;padding:0;margin:-1px;
  overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0;
}

.wrap{max-width:1240px;margin-inline:auto;padding-inline:var(--gut)}

.serif{font-family:var(--serif);font-weight:400}
.num{font-variant-numeric:tabular-nums;font-feature-settings:"tnum" 1}
.eyebrow{
  font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;
  font-weight:600;color:var(--ink-3);
  display:flex;align-items:center;gap:.7em;
}
.eyebrow::before{content:"";width:26px;height:1px;background:var(--rule-2);flex:none}

/* ============================================================ HEADER */
.site-head{
  position:sticky;top:0;z-index:50;
  background:color-mix(in srgb,var(--paper) 90%,transparent);
  backdrop-filter:blur(10px);
  border-bottom:1px solid var(--rule);
}
.head-inner{display:flex;align-items:center;gap:2.2rem;height:70px}
.brand{
  display:flex;align-items:center;gap:.6rem;
  font-family:var(--serif);font-size:1.32rem;letter-spacing:-.01em;
  flex:none;
}
.brand-mark{
  width:26px;height:26px;flex:none;border-radius:5px;
  background:var(--forest);display:grid;place-items:center;
  color:#fff;font-family:var(--sans);font-size:.72rem;font-weight:600;
}
.nav{display:flex;gap:1.75rem;margin-left:auto}
.nav a{
  font-size:.9rem;color:var(--ink-2);font-weight:500;
  padding-block:.35rem;border-bottom:1px solid transparent;
  transition:color .16s ease,border-color .16s ease;
}
.nav a:hover{color:var(--ink);border-bottom-color:var(--rule-2)}
.head-actions{display:flex;align-items:center;gap:.55rem}

.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:.5em;
  padding:.62rem 1.1rem;border-radius:var(--radius-sm);
  font-size:.88rem;font-weight:500;border:1px solid transparent;
  transition:background .16s ease,border-color .16s ease,color .16s ease;
  white-space:nowrap;
}
.btn-primary{background:var(--ink);color:var(--paper)}
.btn-primary:hover{background:#000}
.btn-forest{background:var(--forest);color:#fff}
.btn-forest:hover{background:var(--forest-2)}
.btn-ghost{border-color:var(--rule-2);color:var(--ink)}
.btn-ghost:hover{background:var(--paper-2);border-color:var(--ink-3)}
.btn-lg{padding:.85rem 1.5rem;font-size:.95rem}

/* ---------- mobile menu trigger ---------- */
.nav-toggle{
  display:none;
  width:40px;height:40px;
  align-items:center;justify-content:center;
  border:1px solid var(--rule-2);
  border-radius:var(--radius-sm);
  color:var(--ink);
  flex:none;
}
.nav-toggle:hover{background:var(--paper-2)}
.nav-toggle .icon-close{display:none}
.nav-toggle[aria-expanded="true"] .icon-open{display:none}
.nav-toggle[aria-expanded="true"] .icon-close{display:block}

/* ---------- mobile drawer ---------- */
.mobile-menu{
  display:none;
  border-top:1px solid var(--rule);
  background:var(--paper);
}
.mobile-menu[data-open="true"]{display:block}
.mobile-menu nav{
  padding-block:1.1rem 1.4rem;
  display:grid;gap:.1rem;
}
.mobile-menu a{
  display:flex;align-items:center;justify-content:space-between;
  padding:.78rem .25rem;
  font-size:.95rem;font-weight:500;
  color:var(--ink-2);
  border-bottom:1px solid var(--rule);
  min-height:46px;
}
.mobile-menu a:hover,.mobile-menu a:focus-visible{color:var(--ink)}
.mobile-menu a::after{
  content:"";
  width:7px;height:7px;
  border-right:1.6px solid var(--ink-3);
  border-bottom:1.6px solid var(--ink-3);
  transform:rotate(-45deg);
  flex:none;
}
.mobile-menu .menu-actions{
  display:grid;gap:.55rem;
  margin-top:1.1rem;
}
.mobile-menu .menu-actions .btn{width:100%}
.mobile-menu .menu-label{
  font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;
  color:var(--ink-3);font-weight:600;
  padding:.6rem .25rem .35rem;
}

body.menu-open{overflow:hidden}

/* ============================================================ HERO */
.hero{
  padding-block:clamp(44px,5.5vw,84px) clamp(44px,5vw,72px);
  border-bottom:1px solid var(--rule);
  position:relative;overflow:hidden;
}
.hero-grid{
  display:grid;grid-template-columns:minmax(0,1.22fr) minmax(0,1fr);
  gap:clamp(32px,5vw,76px);align-items:center;
}
.hero h1{
  font-family:var(--serif);font-weight:400;
  font-size:clamp(2.5rem,5.4vw,4.1rem);
  line-height:1.0;letter-spacing:-.022em;
  margin:.6rem 0 0;
}
.hero h1 em{font-style:italic;color:var(--forest);display:block}
.hero-lede{
  margin:1.35rem 0 0;max-width:48ch;
  font-size:clamp(1rem,1.3vw,1.1rem);
  color:var(--ink-2);line-height:1.62;
}

/* search bar — a real element, not decoration */
.search{
  display:flex;gap:.5rem;margin-top:1.7rem;
  background:#fff;border:1px solid var(--rule-2);
  border-radius:var(--radius);padding:.4rem;
  box-shadow:0 1px 2px rgba(22,20,15,.04);
  max-width:520px;
}
.search-field{
  display:flex;align-items:center;gap:.55rem;
  flex:1;min-width:0;padding-left:.7rem;color:var(--ink-3);
}
.search-field svg{flex:none}
.search input{
  border:none;outline:none;background:none;
  font:inherit;font-size:.92rem;color:var(--ink);
  width:100%;min-width:0;padding-block:.55rem;
}
.search input::placeholder{color:var(--ink-3)}

.hero-actions{display:flex;flex-wrap:wrap;gap:.7rem;margin-top:1.1rem;align-items:center}
.hero-note{
  margin-top:1.1rem;font-size:.82rem;color:var(--ink-3);
  display:flex;align-items:center;gap:.45em;
}

/* social proof — real faces */
.proof-row{
  display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;
  margin-top:2.1rem;padding-top:1.4rem;
  border-top:1px solid var(--rule);
}
.facepile{display:flex;align-items:center}
.facepile img,.facepile .face{
  width:34px;height:34px;border-radius:50%;
  border:2px solid var(--paper);
  margin-left:-10px;
  object-fit:cover;background:var(--paper-3);
  display:grid;place-items:center;
  font-size:.68rem;font-weight:600;color:var(--ink-2);
}
.facepile img:first-child,.facepile .face:first-child{margin-left:0}
.proof-text{font-size:.845rem;color:var(--ink-2);line-height:1.45}
.proof-text b{color:var(--ink);font-weight:600}
.proof-stars{display:flex;align-items:center;gap:.28rem;color:var(--amber);font-size:.8rem;font-weight:600}

/* stats */
.stats{display:flex;flex-wrap:wrap;margin-top:1.5rem;gap:0}
.stat{padding-right:1.9rem;margin-right:1.9rem;border-right:1px solid var(--rule)}
.stat:last-child{border-right:0;margin-right:0;padding-right:0}
.stat-val{font-family:var(--serif);font-size:1.55rem;line-height:1;letter-spacing:-.01em}
.stat-lab{
  display:block;margin-top:.4rem;font-size:.72rem;color:var(--ink-3);
  letter-spacing:.05em;text-transform:uppercase;font-weight:500;
}

/* ---------- hero right: a single bold photograph ---------- */
.hero-panel{position:relative}
.hero-figure{
  margin:0;position:relative;
  border-radius:var(--radius-lg);
  overflow:hidden;
  background:var(--paper-3);
  aspect-ratio:4/4.6;
  box-shadow:0 10px 34px -12px rgba(22,20,15,.22);
}
.hero-figure img{
  width:100%;height:100%;
  object-fit:cover;object-position:center 32%;
  display:block;
}
.hero-figure-tag{
  position:absolute;
  left:.85rem;bottom:.85rem;right:.85rem;
  background:color-mix(in srgb,#fff 93%,transparent);
  backdrop-filter:blur(8px);
  border-radius:9px;
  padding:.62rem .75rem;
  display:flex;align-items:center;gap:.6rem;
  box-shadow:0 2px 12px rgba(22,20,15,.12);
}
.tag-lock{
  width:26px;height:26px;flex:none;border-radius:6px;
  background:var(--forest);color:#fff;
  display:grid;place-items:center;
}
.hero-figure-tag b{display:block;font-size:.8rem;font-weight:600;line-height:1.2}
.hero-figure-tag span span{display:block;font-size:.72rem;color:var(--ink-3);margin-top:.06rem}

/* ============================================================ JOB BOARD */
.board{padding-block:clamp(44px,5vw,76px)}
.board-head{
  display:flex;align-items:flex-end;justify-content:space-between;
  gap:2rem;flex-wrap:wrap;padding-bottom:1.35rem;
  border-bottom:1px solid var(--rule);
}
.board-head h2{
  font-family:var(--serif);font-weight:400;
  font-size:clamp(1.8rem,3.2vw,2.45rem);
  line-height:1.05;letter-spacing:-.018em;margin:.42rem 0 0;
}
.board-count{font-size:.82rem;color:var(--ink-3);padding-bottom:.3rem}
.board-count b{color:var(--ink);font-weight:600}

.filters{
  display:flex;flex-wrap:wrap;gap:.5rem;
  padding-block:1.1rem;border-bottom:1px solid var(--rule);
  align-items:center;
}
.chip{
  padding:.42rem .82rem;min-height:34px;
  display:inline-flex;align-items:center;
  border:1px solid var(--rule-2);border-radius:100px;
  font-size:.82rem;font-weight:500;color:var(--ink-2);
  transition:all .15s ease;
}
.chip:hover{border-color:var(--ink-3);color:var(--ink)}
.chip[aria-pressed="true"]{background:var(--ink);color:var(--paper);border-color:var(--ink)}
.filters-spacer{flex:1}
.sortsel{
  padding:.42rem .7rem;min-height:34px;
  border:1px solid var(--rule-2);border-radius:var(--radius-sm);
  background:#fff;font-size:.82rem;color:var(--ink-2);font-family:inherit;
}

/* ---- CARD GRID: 3 up on desktop ---- */
.jobs{
  list-style:none;margin:1.5rem 0 0;padding:0;
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:1.15rem;
}
.job-card{
  background:#fff;border:1px solid var(--rule);
  border-radius:var(--radius-lg);
  padding:1.15rem;
  display:flex;flex-direction:column;
  gap:.75rem;
  transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease;
  position:relative;
}
.job-card:hover{
  border-color:var(--rule-2);
  box-shadow:0 4px 16px rgba(22,20,15,.07);
  transform:translateY(-2px);
}
.job-top{display:flex;align-items:center;justify-content:space-between;gap:.6rem}
.job-cat{
  font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;
  color:var(--ink-3);font-weight:600;
}
.badge{
  display:inline-flex;align-items:center;gap:.35em;
  padding:.24rem .55rem .24rem .45rem;border-radius:100px;
  font-size:.7rem;font-weight:600;white-space:nowrap;
}
.badge-funded{background:var(--forest-wash);color:var(--forest)}
.badge-await{background:var(--amber-wash);color:var(--amber)}
.badge svg{flex:none}

.job-title{
  font-family:var(--serif);font-weight:400;
  font-size:1.2rem;line-height:1.22;letter-spacing:-.008em;
  margin:0;
}
.job-title a::after{content:"";position:absolute;inset:0;border-radius:var(--radius-lg)}

.job-desc{
  margin:0;font-size:.845rem;color:var(--ink-2);line-height:1.55;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;
  overflow:hidden;
}

.job-tags{display:flex;flex-wrap:wrap;gap:.35rem}
.tag{
  font-size:.7rem;padding:.18rem .5rem;border-radius:4px;
  background:var(--paper-2);color:var(--ink-2);font-weight:500;
}

.job-foot{
  margin-top:auto;padding-top:.85rem;border-top:1px solid var(--rule);
  display:flex;align-items:flex-end;justify-content:space-between;gap:.7rem;
}
.poster{display:flex;align-items:center;gap:.5rem;min-width:0}
.poster img,.poster .face{
  width:26px;height:26px;border-radius:50%;object-fit:cover;
  background:var(--paper-3);flex:none;
  display:grid;place-items:center;font-size:.6rem;font-weight:600;color:var(--ink-2);
}
.poster-name{
  font-size:.76rem;color:var(--ink-2);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.job-amt{
  font-family:var(--serif);font-size:1.22rem;line-height:1;
  font-variant-numeric:tabular-nums;white-space:nowrap;text-align:right;
}
.job-amt small{
  display:block;font-family:var(--sans);font-size:.6rem;
  letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);
  margin-top:.22rem;font-weight:500;
}
.job-meta{
  display:flex;gap:.8rem;font-size:.72rem;color:var(--ink-3);
  align-items:center;flex-wrap:wrap;
}
.job-meta span{display:inline-flex;align-items:center;gap:.3em}

.board-more{display:flex;justify-content:center;margin-top:1.9rem}

/* ============================================================ ESCROW */
.escrow{
  border-top:1px solid var(--rule);border-bottom:1px solid var(--rule);
  background:var(--paper-2);padding-block:clamp(40px,4.5vw,64px);
}
.escrow-inner{
  display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2.1fr);
  gap:clamp(28px,4.5vw,60px);align-items:start;
}
.escrow h2{
  font-family:var(--serif);font-weight:400;
  font-size:clamp(1.45rem,2.4vw,1.9rem);
  line-height:1.12;letter-spacing:-.015em;margin:.4rem 0 0;
}
.escrow p{margin:.85rem 0 0;font-size:.875rem;color:var(--ink-2);max-width:36ch;line-height:1.6}
.steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0}
.step{padding-inline:clamp(14px,2vw,26px);border-left:1px solid var(--rule-2)}
.step:first-child{border-left:0;padding-left:0}
.step-n{font-family:var(--serif);font-size:.9rem;color:var(--forest);letter-spacing:.1em}
.step h3{font-size:.92rem;font-weight:600;margin:.5rem 0 0;line-height:1.3}
.step p{margin:.42rem 0 0;font-size:.82rem;color:var(--ink-2);line-height:1.55;max-width:none}

/* ============================================================ FOOTER */
.foot{padding-block:clamp(40px,4.5vw,60px) 2rem}
.foot-grid{
  display:grid;grid-template-columns:minmax(0,1.6fr) repeat(3,minmax(0,.8fr));
  gap:2.4rem;padding-bottom:2.4rem;border-bottom:1px solid var(--rule);
}
.foot-brand p{margin:.8rem 0 0;font-size:.835rem;color:var(--ink-3);max-width:34ch;line-height:1.6}
.foot-col h4{
  font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;
  color:var(--ink-3);font-weight:600;margin:0 0 .8rem;
}
.foot-col ul{list-style:none;margin:0;padding:0;display:grid;gap:.5rem}
.foot-col a{font-size:.845rem;color:var(--ink-2)}
.foot-col a:hover{color:var(--ink);text-decoration:underline;text-underline-offset:3px}
.foot-base{
  display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;
  padding-top:1.4rem;font-size:.775rem;color:var(--ink-3);
}
.demo-note{
  display:inline-flex;align-items:center;gap:.4em;
  padding:.3rem .65rem;border-radius:100px;
  background:var(--amber-wash);color:var(--amber);
  font-size:.73rem;font-weight:600;
}

/* ============================================================ RESPONSIVE */
@media (max-width:1100px){
  .jobs{grid-template-columns:repeat(2,minmax(0,1fr))}
}

/* tablet and below: brand + menu button only.
   The drawer carries Sign in / Post a job, so nothing is lost. */
@media (max-width:960px){
  .head-actions .btn{display:none}
  .head-actions{gap:0;margin-left:auto}
  .nav-toggle{display:inline-flex}
  .nav{display:none}
}

@media (max-width:980px){
  .hero-grid{grid-template-columns:1fr;gap:2.4rem}
  /* image follows the copy on narrow screens — it does not lead */
  .hero-panel{order:2;max-width:520px}
  .hero-figure{aspect-ratio:16/11}
  .escrow-inner{grid-template-columns:1fr}
  .foot-grid{grid-template-columns:1fr 1fr;gap:2rem}
  .hero h1 em{display:inline}
}

@media (max-width:720px){
  .head-inner{gap:.75rem}
  .jobs{grid-template-columns:1fr}
  .steps{grid-template-columns:1fr;gap:1.1rem}
  .step{border-left:0;padding-left:0;border-top:1px solid var(--rule-2);padding-top:1rem}
  .step:first-child{border-top:0;padding-top:0}
  .stat{padding-right:1.1rem;margin-right:1.1rem}
  .foot-grid{grid-template-columns:1fr}
}

@media (max-width:430px){
  .head-inner{gap:.6rem}
  .brand{font-size:1.2rem;letter-spacing:-.015em}
  .nav-toggle{width:42px;height:42px}
  .hero-actions{flex-direction:column;align-items:stretch}
  .hero-actions .btn{width:100%}
  .search{flex-direction:column;gap:.4rem}
  .search-field{padding-left:.5rem}
  .search .btn{width:100%}
  .stat{border-right:0;padding-right:0;margin-right:0}
  .stats{gap:.85rem 0}
  .hero-figure{aspect-ratio:4/3.4}
}

@media (prefers-reduced-motion:reduce){
  *{animation-duration:.01ms!important;transition-duration:.01ms!important}
  .job-card:hover{transform:none}
}
</style>
</head>
<body>

<!-- ============================ HEADER ============================ -->
<header class="site-head">
  <div class="wrap head-inner">
    <a class="brand" href="{{ route('home') }}">
      @if (\App\Support\LogoUploader::url())
      <img class="brand-logo" src="{{ \App\Support\LogoUploader::url() }}"
           alt="{{ \App\Support\SiteSettings::brandName() }}">
    @else
      <span class="brand-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
    @endif
      {{ \App\Support\SiteSettings::brandName() }}
    </a>
    <nav class="nav" aria-label="Main">
      <a href="{{ route('jobs.index') }}">Browse jobs</a>
      <a href="{{ route('freelancers.index') }}">Freelancers</a>
      <a href="#how">How escrow works</a>
      
      
    </nav>
    <div class="head-actions">
      <a class="btn btn-ghost" href="{{ route('login') }}">Sign in</a>
      @auth
        @if (auth()->user()->isPoster())
          <a class="btn btn-primary" href="{{ route('jobs.manage.create') }}">Post a job</a>
        @else
          <a class="btn btn-primary" href="{{ route('jobs.index') }}">Browse jobs</a>
        @endif
      @else
        <a class="btn btn-primary" href="{{ route('register') }}">Post a job</a>
      @endauth
      <button class="nav-toggle" type="button"
              aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
        <svg class="icon-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
        <svg class="icon-close" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- mobile drawer -->
  <div class="mobile-menu" id="mobile-menu" data-open="false">
    <nav class="wrap" aria-label="Mobile">
      <a href="{{ route('jobs.index') }}">Browse jobs</a>
      <a href="{{ route('freelancers.index') }}">Freelancers</a>
      <a href="#how">How escrow works</a>
      
      
      <span class="menu-label">Account</span>
      <div class="menu-actions">
        <a class="btn btn-ghost" href="{{ route('login') }}">Sign in</a>
        @auth
        @if (auth()->user()->isPoster())
          <a class="btn btn-primary" href="{{ route('jobs.manage.create') }}">Post a job</a>
        @else
          <a class="btn btn-primary" href="{{ route('jobs.index') }}">Browse jobs</a>
        @endif
      @else
        <a class="btn btn-primary" href="{{ route('register') }}">Post a job</a>
      @endauth
      </div>
    </nav>
  </div>
</header>

<!-- ============================ HERO ============================ -->
<section class="hero">
  <div class="wrap hero-grid">

    <div class="hero-copy">
      <p class="eyebrow">Escrow-backed freelance work</p>
      <h1 class="serif">Hire with certainty.<em>Get paid without chasing.</em></h1>
      <p class="hero-lede">
        Every job is funded before it goes live. Freelancers see the money is real
        before they write a single proposal — and posters only hire people who can
        see the work is guaranteed.
      </p>

      <form class="search" role="search" onsubmit="return false">
        <span class="search-field">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <label for="q" class="sr-only">Search jobs</label>
          <input id="q" type="search" name="q" placeholder="Search jobs, skills or categories">
        </span>
        <button class="btn btn-forest" type="submit">Search</button>
      </form>

      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="#jobs">Browse funded jobs</a>
        <a class="btn btn-ghost btn-lg" href="{{ route('register') }}">Create an account</a>
      </div>

      <p class="hero-note">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Funds held by the platform, released only when work is accepted.
      </p>

      {{--
        MARKETING COPY — deliberately not connected to the database.

        Product-owner decision: these are illustrative figures for the hero, on
        the same footing as the photography and the headline. They are static by
        design and must NOT be wired to `$stats`, because a dashboard total and a
        marketing headline are answering different questions.

        They live here, in one labelled block, rather than scattered through the
        markup — so the next person to touch this page can see immediately that
        the numbers are copy and not a query.

        If this ever ships to real traffic, replace them with `$stats` values or
        remove the block. A visitor cannot tell the difference, so the decision
        has to be made here rather than discovered later.
      --}}
      <div class="proof-row">
        <div class="facepile" aria-hidden="true">
          <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="" width="34" height="34" loading="lazy">
          <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="" width="34" height="34" loading="lazy">
          <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="" width="34" height="34" loading="lazy">
          <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="" width="34" height="34" loading="lazy">
          <span class="face">+9k</span>
        </div>
        <p class="proof-text">
          <b>12,480 freelancers</b> and <b>3,190 posters</b><br>
          <span class="proof-stars">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z"/></svg>
            4.8 average rating from 2,041 reviews
          </span>
        </p>
      </div>

      {{-- MARKETING COPY — see the note above. Static hero figures, not queries. --}}
      <div class="stats">
        <div class="stat">
          <span class="stat-val num">$128,400</span>
          <span class="stat-lab">In escrow now</span>
        </div>
        <div class="stat">
          <span class="stat-val num">342</span>
          <span class="stat-lab">Jobs funded</span>
        </div>
        <div class="stat">
          <span class="stat-val num">2.4<small style="font-size:.52em">d</small></span>
          <span class="stat-lab">Median release</span>
        </div>
      </div>
    </div>

    <!-- hero right: one bold image, no fake activity feed -->
    <aside class="hero-panel">
      <figure class="hero-figure">
        <img
          src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=1000&q=80"
          alt="A freelancer reviewing work on their phone"
          width="720" height="900" fetchpriority="high">
        <figcaption class="hero-figure-tag">
          <span class="tag-lock" aria-hidden="true">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <span>
            <b>Escrow funded</b>
            <span>before the job goes live</span>
          </span>
        </figcaption>
      </figure>
    </aside>
  </div>
</section>

<!-- ============================ JOB BOARD ============================ -->
<section class="board" id="jobs">
  <div class="wrap">
    <div class="board-head">
      <div>
        <p class="eyebrow">Open now</p>
        <h2 class="serif">Find your favourite jobs</h2>
      </div>
      {{--
        This count sits directly above the actual list, so it must match it.
        The marketing figures in the hero are static copy by product decision;
        a number a visitor can immediately verify against the list below is a
        different matter, and "48" beside one card would simply read as broken.
      --}}
      <p class="board-count">
        @if ($stats['jobs_funded'] > 0)
          <b>{{ number_format($stats['jobs_funded']) }}</b> funded job{{ $stats['jobs_funded'] === 1 ? '' : 's' }} · Open to proposals
        @else
          No jobs open right now
        @endif
      </p>
    </div>

    {{--
      Real categories, as links.

      The prototype had decorative <button>s ("Web Design", "Backend", "Video")
      that filtered nothing and did not correspond to any category in the
      database. A chip that looks pressable and does nothing is a worse
      experience than no chip, and it teaches a visitor the page is inert.

      These are anchors to the listing page with a real category slug, so each one
      actually narrows the results. Only active categories are shown, and the
      block disappears entirely when there are none.
    --}}
    @if ($categories->isNotEmpty())
      <div class="filters" role="group" aria-label="Browse by category">
        <a class="chip" href="{{ route('jobs.index') }}" aria-current="true">All</a>
        @foreach ($categories as $category)
          <a class="chip" href="{{ route('jobs.index', ['category' => $category->slug]) }}">
            {{ $category->name }}
          </a>
        @endforeach
        <span class="filters-spacer"></span>
        <a class="sortsel" href="{{ route('jobs.index') }}" style="text-decoration:none;display:inline-flex;align-items:center">
          See all jobs &rarr;
        </a>
      </div>
    @endif

    <ul class="jobs">
      @forelse ($featuredJobs as $job)
        <li class="job-card">
          <div class="job-top">
            <span class="job-cat">{{ $job->category?->name ?? 'Uncategorised' }}</span>
            {{-- The badge reflects the escrow row, not a status field: a job is
                 only listed once money is actually held. --}}
            <span class="badge badge-funded">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Funded
            </span>
          </div>

          <h3 class="job-title">
            <a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a>
          </h3>

          <p class="job-desc">{{ \Illuminate\Support\Str::limit($job->description, 150) }}</p>

          @if ($job->deadline)
            <div class="job-meta">
              <span>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Due {{ $job->deadline->format('j M Y') }}
              </span>
            </div>
          @endif

          <div class="job-foot">
            <div class="poster">
              {{--
                Initials rather than a stock photo. The poster's real photo is not
                stored, and a random stranger's face presented as the person who
                posted the job is exactly the kind of false detail this project
                has already been bitten by.
              --}}
              <span class="face" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($job->poster?->name ?? 'A', 0, 1)) }}</span>
              <span class="poster-name">{{ $job->poster?->name ?? 'A poster' }}</span>
            </div>
            <p class="job-amt num">{{ $job->budget()->format() }}<small>In escrow</small></p>
          </div>
        </li>
      @empty
        {{--
          Honest empty state. An empty grid with a "browse all jobs" button that
          leads to another empty page is worse than saying so plainly.
        --}}
        <li class="job-card" style="grid-column:1/-1;text-align:center;padding:2.5rem 1.5rem">
          <p style="margin:0 0 .3rem;font-weight:600">No jobs are open yet</p>
          <p style="margin:0;font-size:.86rem;color:var(--ink-3);line-height:1.55">
            Every job here has its budget held in escrow. As soon as one is funded it
            appears on this page.
          </p>
        </li>
      @endforelse
    </ul>

    <div class="board-more">
        {{-- "48 funded jobs" was invented. If there are jobs, say how many; if
             not, the empty state above has already said so. --}}
        {{--
            The count here IS real: this link sits directly under the live job
            board, so quoting a marketing number beside an actual list of jobs
            would be visible nonsense. The hero figures above are copy; this one
            is a label for the content immediately beneath it.
        --}}
        @if ($stats['jobs_funded'] > 0)
          <a class="btn btn-ghost btn-lg" href="{{ route('jobs.index') }}">
            View all {{ number_format($stats['jobs_funded']) }} funded job{{ $stats['jobs_funded'] === 1 ? '' : 's' }}
          </a>
        @else
          <a class="btn btn-ghost btn-lg" href="{{ route('jobs.index') }}">
            Browse all jobs
          </a>
        @endif
    </div>
  </div>
</section>

<!-- ============================ ESCROW ============================ -->
<section class="escrow" id="how">
  <div class="wrap escrow-inner">
    <div>
      <p class="eyebrow">The mechanism</p>
      <h2 class="serif">Money moves once, and only when it should.</h2>
      <p>There is no balance anyone can quietly edit. Every movement is a recorded row with a reason and a timestamp — so any figure can be explained.</p>
    </div>
    <div class="steps">
      <div class="step">
        <p class="step-n">01</p>
        <h3>Poster funds escrow</h3>
        <p>The full budget leaves their available balance before the job is published. Unfunded jobs never appear.</p>
      </div>
      <div class="step">
        <p class="step-n">02</p>
        <h3>Freelancer delivers</h3>
        <p>Work is submitted against a milestone. Delivery and acceptance are attestations — they move no money.</p>
      </div>
      <div class="step">
        <p class="step-n">03</p>
        <h3>Escrow is released</h3>
        <p>The platform releases that milestone to the freelancer. Released once, recorded permanently.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================ FOOTER ============================ -->
<footer class="foot">
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <a class="brand" href="{{ route('home') }}">
          <span class="brand-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
          {{ \App\Support\SiteSettings::brandName() }}
        </a>
        <p>Escrow-backed freelance work. Every listing funded before it goes live.</p>
        <p style="margin-top:.85rem">
          <span class="demo-note">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
            Demonstration build — no real money
          </span>
        </p>
      </div>
      <div class="foot-col">
        <h4>For freelancers</h4>
        <ul>
          <li><a href="{{ route('jobs.index') }}">Browse jobs</a></li>
          <li><a href="{{ route('register') }}">Build a profile</a></li>
          
        </ul>
      </div>
      <div class="foot-col">
        <h4>For posters</h4>
        <ul>
          <li><a href="{{ route('register') }}">Post a job</a></li>
          
          <li><a href="{{ route('jobs.manage.index') }}">Review applicants</a></li>
        </ul>
      </div>
      {{--
        The prototype had a "Platform" column of Terms / Privacy / Docs / Status.
        None of those pages exist, and rather than leave a column of empty <li>s
        the whole column is removed. A footer that promises a terms page and
        leads nowhere is worse than a shorter footer.
      --}}
    </div>
    <div class="foot-base">
      <span>&copy; {{ date('Y') }} {{ \App\Support\SiteSettings::brandName() }}. Demonstration project.</span>
      <span>Site name, logo and contact details are editable by admin.</span>
    </div>
  </div>
</footer>

<script>
(function () {
  var toggle = document.querySelector('.nav-toggle');
  var menu = document.getElementById('mobile-menu');
  if (!toggle || !menu) return;

  function setOpen(open) {
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    menu.dataset.open = String(open);
    document.body.classList.toggle('menu-open', open);
  }

  toggle.addEventListener('click', function () {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });

  // close after following a link inside the drawer
  menu.addEventListener('click', function (e) {
    if (e.target.closest('a')) setOpen(false);
  });

  // Escape closes and returns focus to the trigger
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });

  // close if the viewport grows past the drawer breakpoint (must match CSS)
  var mq = window.matchMedia('(min-width: 961px)');
  mq.addEventListener('change', function (e) { if (e.matches) setOpen(false); });
})();
</script>

</body>
</html>
