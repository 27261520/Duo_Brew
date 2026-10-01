<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Reusable Footer Include
 */
?>

<?php if (isset($showSidebar) && $showSidebar !== false): ?>
        </div><!-- /.container-fluid -->
    </div><!-- /#page-content-wrapper -->
</div><!-- /#wrapper -->
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<!-- Page-specific JS -->
<?php if (isset($extraScripts)): ?>
    <?php foreach ((array)$extraScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($pageScripts)): ?>
    <?= $pageScripts ?>
<?php endif; ?>

</body>
</html>

