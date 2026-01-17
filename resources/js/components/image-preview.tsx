import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';
import { Maximize2, X } from 'lucide-react';

interface ImagePreviewProps {
    src: string;
    alt: string;
    children?: React.ReactNode;
    className?: string;
}

export function ImagePreview({
    src,
    alt,
    children,
    className,
}: ImagePreviewProps) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <div className={`cursor-zoom-in ${className}`}>
                    {children ? (
                        children
                    ) : (
                        <div className="group relative h-full w-full overflow-hidden">
                            <img
                                src={src}
                                alt={alt}
                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div className="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all duration-300 group-hover:bg-black/20 group-hover:opacity-100">
                                <Maximize2 className="h-8 w-8 text-white drop-shadow-md" />
                            </div>
                        </div>
                    )}
                </div>
            </DialogTrigger>
            <DialogContent 
                className="max-h-[95vh] max-w-[95vw] overflow-hidden border-none bg-transparent p-0 shadow-none sm:max-w-screen-xl"
                showCloseButton={false}
            >
                <VisuallyHidden>
                    <DialogTitle>Image Preview</DialogTitle>
                </VisuallyHidden>
                
                <div className="relative flex h-full w-full flex-col items-center justify-center">
                    <DialogClose className="absolute top-4 right-4 z-50 rounded-full bg-black/50 p-2 text-white transition-colors hover:bg-black/70 focus:outline-none">
                        <X className="h-6 w-6" />
                        <span className="sr-only">Close</span>
                    </DialogClose>
                    
                    <img
                        src={src}
                        alt={alt}
                        className="max-h-[90vh] max-w-full rounded-md object-contain shadow-2xl"
                        onClick={(e) => e.stopPropagation()}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
