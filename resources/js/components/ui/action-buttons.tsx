import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Archive, ExternalLink, SquarePen, Plus, Save, Trash2 } from 'lucide-react';
import { type ReactNode } from 'react';

interface ActionButtonProps {
    children: ReactNode;
    tooltip: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    className?: string;
}

function ActionButtonWrapper({
    children,
    tooltip,
    variant = 'outline',
    className = '',
}: ActionButtonProps) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant={variant}
                    size="sm"
                    className={`cursor-pointer ${className}`}
                >
                    {children}
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{tooltip}</p>
            </TooltipContent>
        </Tooltip>
    );
}

// View/Open Action Button
export function ViewActionButton({ tooltip = 'View Details' }: { tooltip?: string }) {
    return (
        <ActionButtonWrapper tooltip={tooltip}>
            <ExternalLink className="h-4 w-4" />
        </ActionButtonWrapper>
    );
}

// Edit Action Button
export function EditActionButton({ tooltip = 'Edit' }: { tooltip?: string }) {
    return (
        <ActionButtonWrapper tooltip={tooltip}>
            <SquarePen className="h-4 w-4" />
        </ActionButtonWrapper>
    );
}

// Archive Action Button
export function ArchiveActionButton({ tooltip = 'Archive' }: { tooltip?: string }) {
    return (
        <ActionButtonWrapper tooltip={tooltip}>
            <Archive className="h-4 w-4 text-[var(--destructive)]" />
        </ActionButtonWrapper>
    );
}

// Delete Action Button (for permanent deletion)
export function DeleteActionButton({ tooltip = 'Delete' }: { tooltip?: string }) {
    return (
        <ActionButtonWrapper tooltip={tooltip} variant="outline">
            <Trash2 className="h-4 w-4 text-[var(--destructive)]" />
        </ActionButtonWrapper>
    );
}

// Add/Create Action Button
export function AddActionButton({ tooltip = 'Add', className = '' }: { tooltip?: string; className?: string }) {
    return (
        <ActionButtonWrapper tooltip={tooltip} className={className}>
            <Plus className="h-4 w-4" />
        </ActionButtonWrapper>
    );
}

// Save Action Button
export function SaveActionButton({ tooltip = 'Save', disabled = false }: { tooltip?: string; disabled?: boolean }) {
    return (
        <Button type="submit" disabled={disabled} size="sm">
            <Save className="mr-2 h-4 w-4" />
            {tooltip}
        </Button>
    );
}
