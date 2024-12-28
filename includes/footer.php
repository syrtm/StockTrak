</div>

<footer class="footer mt-5 py-4 bg-light border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-muted">
                    © <?php echo date('Y'); ?> Stok Takip Sistemi. Tüm hakları saklıdır.
                </span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted">
                    Versiyon 1.0.0 | 
                    <a href="#" class="text-decoration-none text-muted" data-bs-toggle="modal" data-bs-target="#contactModal">
                        <i class="fas fa-envelope me-1"></i>İletişim
                    </a>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- İletişim Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İletişim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><i class="fas fa-phone me-2"></i>+90 (XXX) XXX XX XX</p>
                <p><i class="fas fa-envelope me-2"></i>destek@stoktakip.com</p>
                <p><i class="fas fa-map-marker-alt me-2"></i>Eskişehir, Türkiye</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Aktif menüyü işaretle
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link').forEach(link => {
            if (currentPath === link.getAttribute('href')) {
                link.classList.add('active');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Custom dropdown functionality
        var dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                var dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('show');
                this.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                    dropdown.previousElementSibling.setAttribute('aria-expanded', 'false');
                });
            }
        });
    });
</script>
</body>
</html>