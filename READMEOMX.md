**This is currently only a draft**

## OMX specific
- All packages **require** ID. Based on on this ID (usually it will be order ID from client system) library decides if this is a multilabel or consolidated registration.
- Giving same ID to multiple packages marks as consolidation where first added package will be used as main one and the rest as consolidated. **NOTE!** Consolidated packages can only have FRAGILE as additional service.
- Calling courier returns call ID, which can be used to cancel given call (if cancelation is no longer possible API returns error).