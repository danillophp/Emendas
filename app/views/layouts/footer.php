<?php if (!empty($_SESSION['user'])): ?>
    </main>
  </div>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<?php
$logoRelativePath = 'assets/img/logo/logo-prefeitura.png';
$resolvedLogoRelativePath = resolve_public_asset($logoRelativePath);
$hasInstitutionalLogo = $resolvedLogoRelativePath !== null;
$logoUrl = $hasInstitutionalLogo ? url('public/' . $resolvedLogoRelativePath) : null;
?>

<footer class="institutional-footer mt-4" role="contentinfo" aria-label="Rodapé institucional da Prefeitura">
  <div class="container-fluid px-3 px-lg-4 py-4">
    <div class="row g-4">
      <div class="col-12 col-lg-4">
        <div class="footer-brand d-flex align-items-center gap-3 mb-3">
          <?php if ($hasInstitutionalLogo): ?>
            <img
              src="<?= e($logoUrl ?? '') ?>"
              alt="Logomarca oficial da Prefeitura de Santo Antônio do Descoberto"
              class="footer-logo"
              loading="lazy"
              decoding="async"
            >
          <?php else: ?>
            <div class="footer-logo-placeholder" aria-label="Espaço reservado para logomarca da Prefeitura">
              Logo da Prefeitura
            </div>
          <?php endif; ?>
        </div>

        <h6 class="footer-title">Acesso rápido ao Portal Oficial da Prefeitura</h6>
        <a
          class="footer-portal-link"
          href="https://www.santoantoniododescoberto.go.gov.br"
          target="_blank"
          rel="noopener noreferrer"
        >
          Portal Oficial da Prefeitura
        </a>
      </div>

      <div class="col-12 col-md-6 col-lg-4">
        <h6 class="footer-title">Contato e Atendimento</h6>
        <ul class="footer-list list-unstyled mb-0">
          <li><strong>Fone:</strong> (61) 3626-1289</li>
          <li><strong>E-mail:</strong> secomsade@santoantoniododescoberto.go.gov.br</li>
          <li><strong>Atendimento:</strong> Segunda à Sexta das 8h às 17h</li>
        </ul>
      </div>

      <div class="col-12 col-md-6 col-lg-4">
        <h6 class="footer-title">Endereço e Créditos Institucionais</h6>
        <p class="mb-2 footer-text">
          <strong>Endereço:</strong> Qd. 33, Lt. 24, S/N, Centro, Santo Antônio do Descoberto – GO, 72900-302
        </p>
        <p class="mb-0 footer-text">
          Desenvolvido pela equipe da Secretaria de Comunicação - SECOM<br>
          <strong>Danillo Antônio - Diretor de Programação</strong>
        </p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
