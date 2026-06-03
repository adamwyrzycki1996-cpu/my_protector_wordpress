<?php
/**
 * Review Form Template (Public)
 * 
 * @package MyProtector\Modules\Reviews\templates\public
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = $nonce ?? '';
$showImages = ($show_images ?? 'true') === 'true';
$showRating = ($show_rating ?? 'true') === 'true';
?>

<div class="mp-review-form-container">
    <h3 class="mp-form-title"><?php _e('Write a Review', 'myprotector-platform'); ?></h3>
    
    <form id="mp-review-form" class="mp-review-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="mp_submit_review">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="company_id" value="<?php echo esc_attr($companyId); ?>">

        <!-- Rating -->
        <?php if ($showRating): ?>
            <div class="mp-form-group">
                <label><?php _e('Your Rating', 'myprotector-platform'); ?> *</label>
                <div class="mp-star-rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="mp-star" data-value="<?php echo $i; ?>">★</span>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="mp-rating-input" value="0" required>
                </div>
                <span class="mp-rating-label"></span>
            </div>
        <?php endif; ?>

        <!-- Title -->
        <div class="mp-form-group">
            <label for="mp-review-title"><?php _e('Review Title', 'myprotector-platform'); ?> *</label>
            <input type="text" id="mp-review-title" name="review_title" class="mp-input" 
                   placeholder="<?php esc_attr_e('Summarize your experience in a few words', 'myprotector-platform'); ?>"
                   maxlength="255" required>
        </div>

        <!-- Content -->
        <div class="mp-form-group">
            <label for="mp-review-content"><?php _e('Your Review', 'myprotector-platform'); ?> *</label>
            <textarea id="mp-review-content" name="review_content" class="mp-textarea" rows="6"
                      placeholder="<?php esc_attr_e('Share your experience with others. Be specific about what you liked or disliked.', 'myprotector-platform'); ?>"
                      maxlength="5000" required></textarea>
            <span class="mp-char-count"><span id="mp-char-current">0</span>/5000</span>
        </div>

        <!-- Images -->
        <?php if ($showImages): ?>
            <div class="mp-form-group">
                <label><?php _e('Add Photos (Optional)', 'myprotector-platform'); ?></label>
                <div class="mp-image-upload" id="mp-image-upload">
                    <div class="mp-upload-placeholder" id="mp-upload-placeholder">
                        <span class="mp-upload-icon">📷</span>
                        <span><?php _e('Click to add photos', 'myprotector-platform'); ?></span>
                        <span class="mp-upload-hint">JPG, PNG, GIF, WebP - Max 5MB each</span>
                    </div>
                    <input type="file" id="mp-image-files" name="images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                </div>
                <div class="mp-image-preview" id="mp-image-preview"></div>
            </div>
        <?php endif; ?>

        <!-- Submit -->
        <div class="mp-form-group mp-form-submit">
            <button type="submit" class="mp-submit-btn" id="mp-submit-btn">
                <span class="mp-btn-text"><?php _e('Submit Review', 'myprotector-platform'); ?></span>
                <span class="mp-btn-loading" style="display: none;"><?php _e('Submitting...', 'myprotector-platform'); ?></span>
            </button>
        </div>

        <p class="mp-form-note">
            <?php _e('Your review will be visible after approval.', 'myprotector-platform'); ?>
        </p>
    </form>
</div>

<style>
.mp-review-form-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    max-width: 600px;
}

.mp-review-form-container .mp-form-title {
    margin: 0 0 20px 0;
    font-size: 20px;
}

.mp-review-form .mp-form-group {
    margin-bottom: 20px;
}

.mp-review-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.mp-review-form .mp-input,
.mp-review-form .mp-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.mp-review-form .mp-input:focus,
.mp-review-form .mp-textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
}

.mp-review-form .mp-textarea {
    resize: vertical;
    min-height: 120px;
}

.mp-review-form .mp-char-count {
    display: block;
    text-align: right;
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

/* Star Rating Input */
.mp-star-rating-input {
    display: flex;
    gap: 5px;
    cursor: pointer;
}

.mp-star-rating-input .mp-star {
    font-size: 32px;
    color: #ddd;
    transition: color 0.2s, transform 0.1s;
}

.mp-star-rating-input .mp-star:hover,
.mp-star-rating-input .mp-star.selected {
    color: #f0ad4e;
    transform: scale(1.1);
}

.mp-rating-label {
    display: block;
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

/* Image Upload */
.mp-image-upload {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.mp-image-upload:hover,
.mp-image-upload.dragover {
    border-color: #0073aa;
    background: #f5f9ff;
}

.mp-upload-icon {
    font-size: 32px;
    display: block;
    margin-bottom: 10px;
}

.mp-upload-hint {
    display: block;
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.mp-image-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.mp-image-preview .mp-preview-item {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
}

.mp-image-preview .mp-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mp-image-preview .mp-preview-item .mp-remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    cursor: pointer;
    font-size: 12px;
    line-height: 20px;
}

/* Submit Button */
.mp-submit-btn {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.mp-submit-btn:hover {
    background: #005077;
}

.mp-submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.mp-form-note {
    font-size: 12px;
    color: #999;
    margin-top: 15px;
    text-align: center;
}

.mp-error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.mp-success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}
</style>

<script>
(function($) {
    'use strict';

    // Star rating interaction
    $('.mp-star-rating-input .mp-star').on('click', function() {
        const value = $(this).data('value');
        const input = $(this).closest('.mp-star-rating-input').find('input[name="rating"]');
        const label = $(this).closest('.mp-form-group').find('.mp-rating-label');
        
        input.val(value);
        
        $(this).closest('.mp-star-rating-input').find('.mp-star').each(function() {
            $(this).toggleClass('selected', $(this).data('value') <= value);
        });

        const labels = ['', 'Poor', 'Fair', 'Average', 'Good', 'Excellent'];
        label.text(labels[value] || '');
    });

    // Character count
    $('#mp-review-content').on('input', function() {
        const count = $(this).val().length;
        $('#mp-char-current').text(count);
    });

    // Image upload
    $('#mp-upload-placeholder, #mp-image-files').on('click', function(e) {
        e.stopPropagation();
        $('#mp-image-files').trigger('click');
    });

    $('#mp-image-files').on('change', function() {
        const files = this.files;
        if (!files.length) return;

        const preview = $('#mp-image-preview');
        
        for (let i = 0; i < Math.min(files.length, 5); i++) {
            const file = files[i];
            
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large: ' + file.name);
                continue;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const html = '<div class="mp-preview-item" data-index="' + i + '">' +
                    '<img src="' + e.target.result + '" alt="Preview">' +
                    '<button type="button" class="mp-remove-btn">×</button>' +
                    '</div>';
                preview.append(html);
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove image preview
    $(document).on('click', '.mp-remove-btn', function() {
        $(this).closest('.mp-preview-item').remove();
    });

    // Form submission
    $('#mp-review-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('#mp-submit-btn');
        const btnText = submitBtn.find('.mp-btn-text');
        const btnLoading = submitBtn.find('.mp-btn-loading');

        // Validate
        const rating = form.find('input[name="rating"]').val();
        if (!rating || rating === '0') {
            alert('<?php esc_attr_e('Please select a rating.', 'myprotector-platform'); ?>');
            return;
        }

        const title = form.find('input[name="review_title"]').val().trim();
        if (title.length < 5) {
            alert('<?php esc_attr_e('Title must be at least 5 characters.', 'myprotector-platform'); ?>');
            return;
        }

        const content = form.find('textarea[name="review_content"]').val().trim();
        if (content.length < 20) {
            alert('<?php esc_attr_e('Review must be at least 20 characters.', 'myprotector-platform'); ?>');
            return;
        }

        // Submit
        submitBtn.prop('disabled', true);
        btnText.hide();
        btnLoading.show();

        // Collect form data
        const formData = new FormData(form[0]);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    form.html('<div class="mp-success-message">' + response.data.message + '</div>');
                } else {
                    alert(response.data.message);
                    submitBtn.prop('disabled', false);
                    btnText.show();
                    btnLoading.hide();
                }
            },
            error: function() {
                alert('<?php esc_attr_e('An error occurred. Please try again.', 'myprotector-platform'); ?>');
                submitBtn.prop('disabled', false);
                btnText.show();
                btnLoading.hide();
            }
        });
    });

})(jQuery);
</script>