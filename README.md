# Wembassy Social Media Image Workflow

**ComfyUI workflow for generating professional social media images**

Created by Spock, CTO at Wembassy (https://www.wembassy.com)

---

## Overview

This ComfyUI workflow generates professional, photorealistic images optimized for social media and blog content. Designed for business and corporate use cases with clean, modern aesthetics.

## Supported Formats

| Platform | Dimensions | Aspect Ratio | Use Case |
|----------|-----------|--------------|----------|
| LinkedIn Posts | 1350 x 1080 | 5:4 | Feed posts |
| Blog Featured | 1920 x 1080 | 16:9 | Hero images |
| Blog Thumbnail | 1200 x 630 | 1.91:1 | Social sharing |

## Requirements

### Required Models

- **Checkpoint**: `realisticVisionV60B1_v60B1VAE.safetensors` (recommended)
  - Alternative: SDXL Base (`sd_xl_base_1.0.safetensors`)
  - Alternative: Juggernaut XL
  
- **VAE**: `sdxl_vae.safetensors` or compatible

### ComfyUI Installation

1. Install [ComfyUI](https://github.com/comfyanonymous/ComfyUI)
2. Place checkpoint models in `ComfyUI/models/checkpoints/`
3. Place VAE files in `ComfyUI/models/vae/`
4. Load this workflow: `File → Load` or drag-and-drop `wembassy_social.json`

## Usage

### Quick Start

1. **Load the workflow** - Drag `wembassy_social.json` into ComfyUI
2. **Select checkpoint** - In the CheckpointLoader node, select your model
3. **Set resolution** - In EmptyLatentImage node, set Width/Height:
   - LinkedIn: 1350 x 1080
   - Blog Featured: 1920 x 1080
   - Blog Thumbnail: 1200 x 630
4. **Customize prompt** - Edit the positive prompt for your content
5. **Queue Prompt** - Click "Queue Prompt" to generate

### Recommended Settings

The workflow uses these optimized defaults:

- **Steps**: 30
- **CFG Scale**: 7.0
- **Sampler**: `dpmpp_2m`
- **Scheduler**: `karras`
- **Seed**: Randomize (change to fixed for reproducibility)

### Prompt Structure

**Base positive prompt includes:**
- Professional photography style
- Corporate/office setting context
- Natural lighting
- Photorealistic quality
- No text overlays (for later editing)

**Customize by adding:**
- Subject: "business team meeting", "modern office workspace", "professional handshake"
- Style modifiers: "minimalist", "corporate", "clean lines"
- Details: "golden hour lighting", "bokeh background", "shallow depth of field"

## Optional Enhancements

### Face Detailing (Recommended for portraits)

1. Add `FaceDetailModelLoader` node (Load InsightFace model)
2. Add `FaceDetailer` node between VAEDecode and SaveImage
3. Connect: VAEDecode → FaceDetailer → SaveImage

### Upscaling (For high-res output)

1. Add `LatentUpscale` node after KSampler
2. Set upscale_factor: 1.5x or 2x
3. Add second KSampler with tile_size=64
4. Connect: KSampler → LatentUpscale → KSampler2 → VAEDecode

## Output

Images are saved to your ComfyUI output directory with prefix `wembassy_social_output_`

Default location: `ComfyUI/output/`

## Tips for Best Results

1. **Match resolution to platform** - Use exact dimensions for sharpest results
2. **Use specific prompts** - Vague prompts give inconsistent results
3. **Include lighting terms** - "natural lighting", "soft shadows", "professional studio"
4. **Avoid CLIP_SKIP > 2** - Can reduce prompt coherence
5. **Batch generation** - Generate 4-8 images, select the best

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Blurry images | Increase steps to 40-50, use higher CFG (7.5-8) |
| Washed out colors | Check VAE is loaded correctly, try different VAE |
| Face distortion | Enable FaceDetailer node |
| Slow generation | Use GPU with 8GB+ VRAM, try fp16 checkpoints |
| Model not found | Verify model paths in CheckpointLoader |

## License

MIT License - Feel free to use and modify for your projects.

---

**Wembassy** | Web Development, Digital Marketing & AI Automation
- Website: https://www.wembassy.com
- LinkedIn: https://www.linkedin.com/company/wembassy
