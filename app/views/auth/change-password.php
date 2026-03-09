<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card auth-card">
      <div class="card-body p-4">
        <h4 class="mb-2">Troca obrigatória de senha</h4>
        <p class="text-muted">Para sua segurança, defina uma nova senha antes de continuar.</p>

        <form method="post" action="<?= url('change-password') ?>">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <div class="mb-3">
            <label class="form-label">Nova senha</label>
            <input class="form-control" name="password" type="password" minlength="8" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmar nova senha</label>
            <input class="form-control" name="password_confirm" type="password" minlength="8" required>
          </div>
          <button class="btn btn-success w-100"><i class="bi bi-shield-lock"></i> Salvar nova senha</button>
        </form>
      </div>
    </div>
  </div>
</div>
