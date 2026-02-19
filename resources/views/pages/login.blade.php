@extends('layouts.guest')

@section('title', 'Login — Techbase GRC')

@section('content')
  <div class="card" style="width:100%; max-width:460px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
      <div class="logo" style="width:42px; height:42px;"></div>
      <div>
        <div style="font-weight:750; letter-spacing:.2px;">Techbase GRC</div>
        <div class="muted" style="font-size:12px;">Entre para acessar o dashboard</div>
      </div>
    </div>

    @if ($errors->any())
      <div class="chip bad" style="display:block; padding:10px 12px; border-radius:12px; margin:12px 0;">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
      @csrf

      <div class="row">
        <div class="field">
          <label>Email</label>
          <input name="email" type="email" value="{{ old('email') }}" required />
        </div>
      </div>

      <div class="row" style="margin-top:10px;">
        <div class="field">
          <label>Password</label>
          <input name="password" type="password" required />
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
        <button class="btn primary" type="submit">Entrar</button>
      </div>

      <div class="muted" style="margin-top:12px; font-size:12px; line-height:1.55;">
        Mock users:
        <b>admin@techbase.local</b> / <b>admin123</b> •
        <b>grc@techbase.local</b> / <b>grc123</b> •
        <b>viewer@techbase.local</b> / <b>viewer123</b>
      </div>
    </form>
  </div>
@endsection
