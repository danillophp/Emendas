<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card auth-card">
      <div class="card-body p-4 p-md-5">
        <h4 class="mb-2">Troca obrigatória de senha</h4>
        <p class="text-muted">Para sua segurança, cadastre uma nova senha para continuar.</p>

        <form method="post" action="<?= url('change-password') ?>" novalidate>
          <!-- Token CSRF obrigatório no fluxo sensível de alteração de credenciais -->
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Nova senha</label>
            <input
              class="form-control form-control-lg"
              name="password"
              type="password"
              minlength="8"
              placeholder="Mínimo de 8 caracteres"
              required
            >
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Confirmar nova senha</label>
            <input
              class="form-control form-control-lg"
              name="password_confirm"
              type="password"
              minlength="8"
              placeholder="Repita a nova senha"
              required
            >
          </div>

          <button class="btn btn-success btn-lg w-100" type="submit">
            <i class="bi bi-shield-lock"></i> Salvar nova senha
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
