<div class="auth-page d-flex align-items-center justify-content-center">
  <div class="auth-wrapper container">
    <div class="row g-0 shadow-lg rounded-4 overflow-hidden auth-panel">
      <div class="col-lg-6 auth-hero d-none d-lg-flex flex-column justify-content-center p-5">
        <h2 class="fw-bold text-white mb-3">Gestão de Emendas Governamentais</h2>
        <p class="text-white-50 mb-0">
          Ambiente seguro para acompanhamento de demandas, prazos e notificações institucionais.
        </p>
      </div>

      <div class="col-lg-6 bg-white">
        <div class="p-4 p-md-5">
          <div class="text-center mb-4">
            <h4 class="fw-bold mb-1 text-primary">Acesso ao Sistema</h4>
            <p class="text-muted mb-0">Entre com seu usuário institucional</p>
          </div>

          <form method="post" action="<?= url('login') ?>" autocomplete="off" novalidate>
            <!-- Token CSRF obrigatório para mitigar ataques de falsificação de requisição -->
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

            <div class="mb-3">
              <label class="form-label fw-semibold">Usuário</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input
                  type="text"
                  name="usuario"
                  class="form-control"
                  placeholder="Digite seu usuário"
                  required
                >
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Senha</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input
                  type="password"
                  name="senha"
                  class="form-control"
                  placeholder="Digite sua senha"
                  required
                >
              </div>
            </div>

            <button class="btn btn-primary btn-lg w-100 btn-icon" type="submit">
              <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
