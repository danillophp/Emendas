<div class="auth-page d-flex align-items-center justify-content-center">
  <div class="col-lg-4 col-md-6">
    <div class="card auth-card">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
          <h4 class="fw-bold mb-1 text-primary">Sistema de Emendas</h4>
          <p class="text-muted mb-0">Acesso institucional</p>
        </div>
        <form method="post" action="<?= url('login') ?>">
          <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
          <div class="mb-3">
            <label class="form-label">Usuário</label>
            <input type="text" name="usuario" class="form-control form-control-lg" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control form-control-lg" required>
          </div>
          <button class="btn btn-primary btn-lg w-100 btn-icon"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
