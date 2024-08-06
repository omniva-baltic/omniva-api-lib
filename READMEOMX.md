**This is currently only a draft**
Initial release is ment to be used as drop in from previous version. That means your code should mostly work unchanged
except for below mentioned changes. Library will try to convert old parameters to OMX structure.

## OMX specific
- All packages **require** ID. Based on on this ID (usually it will be order ID from client system) library decides if this is a multilabel or consolidated registration.
- Giving same ID to multiple packages marks as consolidation where first added package will be used as main one and the rest as consolidated. **NOTE!** Consolidated packages can only have FRAGILE as additional service.
- Calling courier returns call ID, which can be used to cancel given call (if cancelation is no longer possible API returns error).