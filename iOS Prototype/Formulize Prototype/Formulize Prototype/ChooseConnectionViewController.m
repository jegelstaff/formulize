//
//  ChooseConnectionViewController.m
//  Formulize Prototype
//
//  Created by Mary Nelly on 10-25-13.
//  Copyright (c) 2013 Laurentian University. All rights reserved.
//

#import "ChooseConnectionViewController.h"

@implementation ChooseConnectionViewController
{
    NSArray *connections;
}
@synthesize tableView;


- (void)viewDidLoad
{
    [super viewDidLoad];
	connections = [NSArray arrayWithObjects:@"Connection1", @"Connection2", nil];
}

- (void)viewDidUnload {

    [super viewDidUnload];
}

#pragma mark - TableView Data Source Methods

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
   return [connections count];
}


- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    static NSString *simpleTableIdentifier = @"connectCell";
    
    UITableViewCell *cell = [tableView dequeueReusableCellWithIdentifier:simpleTableIdentifier];
    
    if (cell == nil) {
        cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:simpleTableIdentifier];
    }
    
    cell.textLabel.text = [connections objectAtIndex:indexPath.row];
    return cell;

}


@end
