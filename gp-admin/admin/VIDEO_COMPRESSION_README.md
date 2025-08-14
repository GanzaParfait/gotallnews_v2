# Video Compression Feature

## Overview
The video compression feature automatically optimizes uploaded video files to reduce file size while maintaining visual quality. This helps improve website performance and user experience.

## How It Works

### 1. Automatic Compression
- Videos are automatically compressed after upload if they exceed 100MB
- Uses FFmpeg for high-quality compression
- Maintains original file if compression fails or isn't effective

### 2. Compression Settings
The system uses intelligent compression based on file size:

- **Very Large Files (>500MB)**: Aggressive compression (CRF 28) for maximum size reduction
- **Large Files (300-500MB)**: Moderate compression (CRF 25) for balanced quality/size
- **Medium Files (200-300MB)**: Light compression (CRF 23) for minimal quality loss
- **Small Files (<200MB)**: Minimal compression (CRF 20) to preserve quality

### 3. Quality Preservation
- **CRF (Constant Rate Factor)**: Lower values = higher quality
- **Audio Quality**: Maintained at 128k-256k depending on compression level
- **Fast Start**: Optimized for web streaming
- **Format**: Converts to H.264 video + AAC audio for maximum compatibility

## Requirements

### FFmpeg Installation
Video compression requires FFmpeg to be installed on your server:

**Windows (XAMPP):**
1. Download FFmpeg from https://ffmpeg.org/download.html
2. Extract to a folder (e.g., `C:\ffmpeg`)
3. Add `C:\ffmpeg\bin` to your system PATH environment variable
4. Restart your web server

**Linux:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

### Verification
The system automatically detects if FFmpeg is available. If not installed:
- Videos will be uploaded without compression
- An error log entry will be created
- Original file size will be preserved

## Benefits

### Performance
- **Faster Loading**: Smaller files load quicker
- **Bandwidth Savings**: Reduced data transfer for users
- **Storage Optimization**: More efficient server storage usage

### Quality
- **Smart Compression**: Only compresses when beneficial
- **Quality Threshold**: Minimum 10% size reduction required
- **Fallback Protection**: Original file preserved if compression fails

### User Experience
- **Smooth Playback**: Optimized for web streaming
- **Mobile Friendly**: Smaller files work better on mobile devices
- **Progressive Loading**: Fast start optimization for immediate playback

## Technical Details

### Compression Algorithm
- **Video Codec**: H.264 (libx264)
- **Audio Codec**: AAC
- **Container**: MP4 with fast start
- **Preset**: Adaptive based on file size

### File Processing
1. Original file uploaded and validated
2. FFmpeg analyzes video properties
3. Compression settings determined automatically
4. Compressed file created with new filename
5. Original file deleted if compression successful
6. File size updated in database

### Error Handling
- FFmpeg not available → Skip compression
- Compression fails → Keep original file
- Insufficient size reduction → Keep original file
- All errors logged for debugging

## Monitoring

### Log Files
Check your PHP error log for compression information:
- Success: `Video compression successful: [size] -> [size] bytes ([X]% reduction)`
- Skipped: `Video file size is already optimal, skipping compression`
- Failed: `Video compression failed: [error details]`

### Database
The `video_posts` table stores the actual file size after compression:
- `VideoSize` field shows compressed file size
- `VideoFormat` indicates if compression was applied

## Customization

### Compression Thresholds
Modify these values in `VideoManager.php`:
```php
$maxTargetSize = 100 * 1024 * 1024; // 100MB target
$compressionRatio = 0.9; // 10% minimum reduction
```

### Quality Settings
Adjust CRF values in `getCompressionSettings()` method:
- Lower CRF = Higher quality, larger files
- Higher CRF = Lower quality, smaller files
- Range: 18-28 (18 = visually lossless, 28 = acceptable quality)

## Troubleshooting

### Common Issues

1. **No Compression Applied**
   - Check if FFmpeg is installed and in PATH
   - Verify file size exceeds 100MB threshold
   - Check PHP error logs for FFmpeg errors

2. **Compression Fails**
   - Ensure FFmpeg has write permissions to upload directory
   - Check available disk space
   - Verify video file format is supported

3. **Quality Too Low**
   - Adjust CRF values in compression settings
   - Lower the compression threshold
   - Use higher quality presets

### Performance Tips
- Monitor server CPU usage during compression
- Consider background processing for large files
- Implement queue system for high-traffic sites
- Cache compressed files to avoid re-processing

## Support
For issues or questions about video compression:
1. Check PHP error logs
2. Verify FFmpeg installation
3. Test with different file sizes and formats
4. Review compression settings and thresholds
